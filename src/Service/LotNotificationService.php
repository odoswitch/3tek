<?php

namespace App\Service;

use App\Entity\Lot;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class LotNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private Environment $twig,
        private string $projectDir
    ) {
    }

    public function notifyUsersAboutNewLot(Lot $lot): void
    {
        // Récupérer tous les utilisateurs vérifiés qui ont accès à la catégorie du lot
        $users = $this->userRepository->createQueryBuilder('u')
            ->innerJoin('u.categorie', 'c')
            ->where('c = :category')
            ->andWhere('u.isVerified = 1')
            ->setParameter('category', $lot->getCat())
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
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
            ->from(new Address('noreply@3tek-europe.com', '3Tek-Europe'))
            ->to($user->getEmail())
            ->subject('Nouveau lot disponible : ' . $lot->getName());

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

        // Envoyer l'email
        $this->mailer->send($email);
    }
}
