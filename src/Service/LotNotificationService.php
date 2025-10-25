<?php

namespace App\Service;

use App\Entity\Lot;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class LotNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private Environment $twig,
        private UrlGeneratorInterface $router,
        private EmailLoggerService $emailLogger,
        private string $projectDir,
        private string $mailFromAddress,
        private string $mailFromName
    ) {
    }

    public function notifyUsersAboutNewLot(Lot $lot): void
    {
        // Récupérer les types du lot
        $lotTypes = $lot->getTypes()->toArray();
        
        if (empty($lotTypes)) {
            error_log(sprintf('Le lot "%s" (ID: %d) n\'a aucun type associé !', $lot->getName(), $lot->getId()));
            return;
        }
        
        // Logger les types du lot
        $typeNames = array_map(fn($type) => $type->getName(), $lotTypes);
        error_log(sprintf(
            'Lot "%s" (ID: %d) - Types: %s, Catégorie: %s',
            $lot->getName(),
            $lot->getId(),
            implode(', ', $typeNames),
            $lot->getCat()->getName()
        ));
        
        // Récupérer tous les utilisateurs vérifiés qui ont accès à la catégorie ET au type du lot
        $qb = $this->userRepository->createQueryBuilder('u')
            ->where(':category MEMBER OF u.categorie')
            ->andWhere('u.isVerified = 1')
            ->setParameter('category', $lot->getCat());
        
        // Ajouter la condition pour les types (au moins un type en commun)
        $typeConditions = [];
        foreach ($lotTypes as $index => $type) {
            $paramName = 'type' . $index;
            $typeConditions[] = ':' . $paramName . ' = u.type';
            $qb->setParameter($paramName, $type);
        }
        
        if (!empty($typeConditions)) {
            $qb->andWhere('(' . implode(' OR ', $typeConditions) . ')');
        }
        
        $users = $qb->getQuery()->getResult();

        // Logger le nombre d'utilisateurs trouvés
        error_log(sprintf(
            'Notification nouveau lot "%s" - %d utilisateur(s) trouvé(s) avec les bons critères',
            $lot->getName(),
            count($users)
        ));

        if (count($users) === 0) {
            error_log('Aucun utilisateur trouvé pour cette catégorie et ce(s) type(s) !');
        }

        foreach ($users as $user) {
            error_log(sprintf(
                'Envoi email à : %s (%s %s) - Type: %s, Catégories: %s',
                $user->getEmail(),
                $user->getName(),
                $user->getLastname(),
                $user->getType() ? $user->getType()->getName() : 'N/A',
                implode(', ', $user->getCategorie()->map(fn($cat) => $cat->getName())->toArray())
            ));
            $this->sendNotificationEmail($user, $lot);
        }
    }

    private function sendNotificationEmail($user, Lot $lot): void
    {
        // Récupérer la première image
        $firstImage = $lot->getImages()->first();
        $imageUrl = null;
        $imagePath = null;
        
        if ($firstImage && $firstImage->getImageName()) {
            $imagePath = $this->projectDir . '/public/uploads/lot_images/' . $firstImage->getImageName();
            // URL absolue pour l'image - sera générée dynamiquement
            $imageUrl = $this->router->generate('app_dash', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            $imageUrl = str_replace('/dash', '/uploads/lot_images/' . $firstImage->getImageName(), $imageUrl);
        }

        // URL absolue pour le logo - sera générée dynamiquement
        $baseUrl = $this->router->generate('app_dash', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $logoUrl = str_replace('/dash', '/images/3tek-logo.png', $baseUrl);
        $logoPath = $this->projectDir . '/public/images/3tek-logo.png';

        // Créer l'email
        $email = (new Email())
            ->from(new Address($this->mailFromAddress, $this->mailFromName))
            ->to($user->getEmail())
            ->replyTo($this->mailFromAddress)
            ->subject('Nouveau lot disponible : ' . $lot->getName());

        // Ajouter des en-têtes pour améliorer la délivrabilité
        $headers = $email->getHeaders();
        $headers->addTextHeader('X-Mailer', '3Tek-Europe Notification System');
        $headers->addTextHeader('X-Priority', '3');
        $headers->addTextHeader('Importance', 'Normal');

        // Ajouter le contenu HTML avec URLs absolues
        $email->html(
            $this->twig->render('emails/new_lot_notification.html.twig', [
                'user' => $user,
                'lot' => $lot,
                'hasImage' => $firstImage !== false && $imagePath && file_exists($imagePath),
                'imageUrl' => $imageUrl,
                'logoUrl' => $logoUrl,
            ])
        );
        
        // Ajouter une version texte pour améliorer la délivrabilité
        $email->text(sprintf(
            "Bonjour %s,\n\nUn nouveau lot est disponible : %s\n\nConnectez-vous à votre espace client pour le découvrir.\n\nCordialement,\nL'équipe 3Tek-Europe",
            $user->getName(),
            $lot->getName()
        ));

        // Envoyer l'email et logger
        try {
            $this->mailer->send($email);
            $this->emailLogger->logSuccess(
                $user->getEmail(),
                'Nouveau lot disponible : ' . $lot->getName(),
                'notification_nouveau_lot',
                [
                    'lot_id' => $lot->getId(),
                    'lot_name' => $lot->getName(),
                    'user_id' => $user->getId(),
                    'user_name' => $user->getName() . ' ' . $user->getLastname()
                ]
            );
        } catch (\Exception $e) {
            // Logger l'erreur mais continuer pour les autres utilisateurs
            $this->emailLogger->logError(
                $user->getEmail(),
                'Nouveau lot disponible : ' . $lot->getName(),
                'notification_nouveau_lot',
                $e->getMessage(),
                [
                    'lot_id' => $lot->getId(),
                    'lot_name' => $lot->getName(),
                    'user_id' => $user->getId(),
                    'user_name' => $user->getName() . ' ' . $user->getLastname()
                ]
            );
            // Ne pas throw pour permettre l'envoi aux autres utilisateurs
        }
    }
}
