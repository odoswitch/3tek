<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\LotRepository;
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
    public function create(int $lotId, Request $request, LotRepository $lotRepository): Response
    {
        $lot = $lotRepository->find($lotId);
        
        if (!$lot) {
            $this->addFlash('error', 'Lot introuvable');
            return $this->redirectToRoute('app_dash');
        }

        if ($lot->getQuantite() <= 0) {
            $this->addFlash('error', 'Ce lot n\'est plus disponible');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        $user = $this->getUser();
        $quantite = (int) $request->request->get('quantite', 1);

        if ($quantite > $lot->getQuantite()) {
            $this->addFlash('error', 'Quantité demandée non disponible');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        // Créer la commande
        $commande = new Commande();
        $commande->setUser($user);
        $commande->setLot($lot);
        $commande->setQuantite($quantite);
        $commande->setPrixUnitaire($lot->getPrix());
        $commande->setPrixTotal($lot->getPrix() * $quantite);
        $commande->setStatut('en_attente');

        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        // Envoyer l'email de confirmation
        $this->sendCommandeConfirmation($commande);

        $this->addFlash('success', 'Votre commande a été enregistrée ! Vous recevrez un email de confirmation avec les instructions de paiement.');
        
        return $this->redirectToRoute('app_commande_view', ['id' => $commande->getId()]);
    }

    #[Route('/commande/mes-commandes', name: 'app_mes_commandes')]
    public function mesCommandes(): Response
    {
        $user = $this->getUser();
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
}
