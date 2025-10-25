<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\FileAttente;
use App\Repository\LotRepository;
use App\Repository\FileAttenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class CommandeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private Environment $twig,
        private string $projectDir
    ) {
    }

    #[Route('/commande/create/{lotId}', name: 'app_commande_create', methods: ['POST'])]
    public function create(int $lotId, Request $request, LotRepository $lotRepository, FileAttenteRepository $fileAttenteRepository): Response
    {
        $lot = $lotRepository->find($lotId);
        
        if (!$lot) {
            $this->addFlash('error', 'Lot introuvable');
            return $this->redirectToRoute('app_dash');
        }

        $user = $this->getUser();

        // Vérifier si le lot est déjà réservé
        if ($lot->isReserve()) {
            // Vérifier si l'utilisateur est déjà dans la file d'attente
            if ($fileAttenteRepository->isUserInQueue($lot, $user)) {
                $this->addFlash('warning', 'Vous êtes déjà dans la file d\'attente pour ce lot');
                return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
            }

            // Ajouter à la file d'attente
            $fileAttente = new FileAttente();
            $fileAttente->setLot($lot);
            $fileAttente->setUser($user);
            $fileAttente->setPosition($fileAttenteRepository->getNextPosition($lot));
            
            $this->entityManager->persist($fileAttente);
            $this->entityManager->flush();

            $this->addFlash('info', 'Ce lot est actuellement réservé. Vous avez été ajouté à la file d\'attente (position ' . $fileAttente->getPosition() . ')');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        // Vérifier si le lot est disponible
        if (!$lot->isDisponible()) {
            $this->addFlash('error', 'Ce lot n\'est plus disponible');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        $quantite = (int) $request->request->get('quantite', 1);

        if ($quantite > $lot->getQuantite()) {
            $this->addFlash('error', 'Quantité demandée non disponible');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        // Créer la commande et réserver le lot
        $commande = new Commande();
        $commande->setUser($user);
        $commande->setLot($lot);
        $commande->setQuantite($quantite);
        $commande->setPrixUnitaire($lot->getPrix());
        $commande->setPrixTotal($lot->getPrix() * $quantite);
        $commande->setStatut('reserve');

        // Persister d'abord la commande
        $this->entityManager->persist($commande);
        $this->entityManager->flush();
        
        // Décrémenter la quantité dès la réservation avec une requête directe
        $nouvelleQuantite = $lot->getQuantite() - $quantite;
        
        $connection = $this->entityManager->getConnection();
        
        // Marquer le lot comme réservé SEULEMENT si le stock atteint 0
        if ($nouvelleQuantite <= 0) {
            $sql = 'UPDATE lot SET quantite = :quantite, statut = :statut, reserve_par_id = :user_id, reserve_at = :reserve_at WHERE id = :id';
            $connection->executeStatement($sql, [
                'quantite' => 0,
                'statut' => 'reserve',
                'user_id' => $user->getId(),
                'reserve_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'id' => $lot->getId()
            ]);
        } else {
            $sql = 'UPDATE lot SET quantite = :quantite WHERE id = :id';
            $connection->executeStatement($sql, [
                'quantite' => $nouvelleQuantite,
                'id' => $lot->getId()
            ]);
        }
        
        // Rafraîchir l'entité pour avoir les nouvelles valeurs
        $this->entityManager->refresh($lot);

        // Envoyer l'email de confirmation au client
        $this->sendCommandeConfirmation($commande);
        
        // Envoyer une notification à l'admin
        $this->sendAdminNotification($commande);

        $this->addFlash('success', 'Votre commande a été enregistrée et le lot est maintenant réservé pour vous ! Vous recevrez un email de confirmation.');
        
        return $this->redirectToRoute('app_commande_view', ['id' => $commande->getId()]);
    }

    #[Route('/commande/mes-commandes', name: 'app_mes_commandes')]
    public function mesCommandes(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $commandes = $user->getCommandes();

        return $this->render('commande/list.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/{id}', name: 'app_commande_view', requirements: ['id' => '\d+'])]
    public function view(Commande $commande): Response
    {
        if ($commande->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('commande/view.html.twig', [
            'commande' => $commande,
        ]);
    }

    private function sendCommandeConfirmation(Commande $commande): void
    {
        $user = $commande->getUser();
        $lot = $commande->getLot();
        
        // Générer l'URL du logo dynamiquement
        $baseUrl = $this->generateUrl('app_dash', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $logoUrl = str_replace('/dash', '/images/3tek-logo.png', $baseUrl);

        $email = (new Email())
            ->from(new Address('noreply@3tek-europe.com', '3Tek-Europe'))
            ->to($user->getEmail())
            ->subject('Confirmation de commande ' . $commande->getNumeroCommande())
            ->html(
                $this->twig->render('emails/commande_confirmation.html.twig', [
                    'commande' => $commande,
                    'user' => $user,
                    'lot' => $lot,
                    'logoUrl' => $logoUrl,
                ])
            );

        $this->mailer->send($email);
    }

    private function sendAdminNotification(Commande $commande): void
    {
        $user = $commande->getUser();
        $lot = $commande->getLot();
        
        // Récupérer tous les utilisateurs avec le rôle ADMIN
        $admins = $this->entityManager->getRepository(\App\Entity\User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();
        
        if (empty($admins)) {
            return; // Pas d'admin trouvé
        }
        
        foreach ($admins as $admin) {
            $email = (new Email())
                ->from(new Address('noreply@3tek-europe.com', '3Tek-Europe'))
                ->to($admin->getEmail())
                ->replyTo('noreply@3tek-europe.com')
                ->subject('Nouvelle commande : ' . $commande->getNumeroCommande())
                ->html(sprintf(
                    '<h2>Nouvelle commande reçue</h2>
                    <p>Une nouvelle commande vient d\'être passée sur votre plateforme.</p>
                    <hr>
                    <p><strong>N° Commande :</strong> %s</p>
                    <p><strong>Client :</strong> %s %s</p>
                    <p><strong>Email :</strong> %s</p>
                    <p><strong>Téléphone :</strong> %s</p>
                    <p><strong>Entreprise :</strong> %s</p>
                    <hr>
                    <p><strong>Lot commandé :</strong> %s</p>
                    <p><strong>Quantité :</strong> %d</p>
                    <p><strong>Prix unitaire :</strong> %.2f €</p>
                    <p><strong>Total :</strong> %.2f €</p>
                    <hr>
                    <p><strong>Stock restant :</strong> %d</p>
                    <p><strong>Statut du lot :</strong> %s</p>
                    <hr>
                    <p><a href="https://app.3tek-europe.com/admin/?crudAction=detail&crudControllerFqcn=App%%5CController%%5CAdmin%%5CCommandeCrudController&entityId=%d" style="display: inline-block; padding: 12px 30px; background: #0066cc; color: white; text-decoration: none; border-radius: 5px;">Voir la commande dans l\'admin</a></p>
                    <p>Cordialement,<br>Système 3Tek-Europe</p>',
                    $commande->getNumeroCommande(),
                    $user->getName(),
                    $user->getLastname(),
                    $user->getEmail(),
                    $user->getPhone() ?? 'Non renseigné',
                    $user->getOffice() ?? 'Non renseigné',
                    $lot->getName(),
                    $commande->getQuantite(),
                    $commande->getPrixUnitaire(),
                    $commande->getPrixTotal(),
                    $lot->getQuantite(),
                    $lot->getStatutLabel(),
                    $commande->getId()
                ))
                ->text(sprintf(
                    "Nouvelle commande reçue\n\nN° Commande : %s\nClient : %s %s\nEmail : %s\n\nLot : %s\nQuantité : %d\nTotal : %.2f €\n\nStock restant : %d\nStatut : %s\n\nConnectez-vous à l'admin pour gérer cette commande.",
                    $commande->getNumeroCommande(),
                    $user->getName(),
                    $user->getLastname(),
                    $user->getEmail(),
                    $lot->getName(),
                    $commande->getQuantite(),
                    $commande->getPrixTotal(),
                    $lot->getQuantite(),
                    $lot->getStatutLabel()
                ));
            
            // Ajouter des en-têtes
            $headers = $email->getHeaders();
            $headers->addTextHeader('X-Mailer', '3Tek-Europe Notification System');
            $headers->addTextHeader('X-Priority', '2');
            $headers->addTextHeader('Importance', 'High');
            
            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                // Logger l'erreur mais ne pas bloquer le processus
                error_log('Erreur envoi email admin: ' . $e->getMessage());
            }
        }
    }
}
