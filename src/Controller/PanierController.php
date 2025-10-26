<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Commande;
use App\Repository\LotRepository;
use App\Repository\PanierRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

#[Route('/panier')]
class PanierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private Environment $twig,
        private string $projectDir
    ) {}

    #[Route('', name: 'app_panier')]
    public function index(PanierRepository $panierRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $items = $panierRepository->findByUser($user);
        $total = $panierRepository->getTotalByUser($user);

        return $this->render('panier/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/add/{lotId}', name: 'app_panier_add', methods: ['POST'])]
    public function add(int $lotId, Request $request, LotRepository $lotRepository, PanierRepository $panierRepository): Response
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

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $quantite = (int) $request->request->get('quantite', 1);

        if ($quantite > $lot->getQuantite()) {
            $this->addFlash('error', 'Quantité demandée non disponible');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        // Vérifier si le lot est déjà dans le panier
        $existingItem = $this->entityManager->getRepository(Panier::class)->findOneBy([
            'user' => $user,
            'lot' => $lot
        ]);

        if ($existingItem) {
            $newQuantite = $existingItem->getQuantite() + $quantite;
            if ($newQuantite > $lot->getQuantite()) {
                $this->addFlash('error', 'Quantité totale dépasse le stock disponible');
                return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
            }
            $existingItem->setQuantite($newQuantite);
        } else {
            $panierItem = new Panier();
            $panierItem->setUser($user);
            $panierItem->setLot($lot);
            $panierItem->setQuantite($quantite);
            $this->entityManager->persist($panierItem);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Lot ajouté au panier');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/update/{id}', name: 'app_panier_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $panierRepository = $this->entityManager->getRepository(Panier::class);
        $panier = $panierRepository->find($id);

        if (!$panier) {
            throw $this->createNotFoundException('Article du panier non trouvé');
        }

        if ($panier->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $quantite = (int) $request->request->get('quantite', 1);

        if ($quantite > $panier->getLot()->getQuantite()) {
            $this->addFlash('error', 'Quantité demandée non disponible');
            return $this->redirectToRoute('app_panier');
        }

        $panier->setQuantite($quantite);
        $this->entityManager->flush();

        $this->addFlash('success', 'Quantité mise à jour');
        return $this->redirectToRoute('app_panier');
    }

    #[Route('/remove/{id}', name: 'app_panier_remove')]
    public function remove(int $id): Response
    {
        $panierRepository = $this->entityManager->getRepository(Panier::class);
        $panier = $panierRepository->find($id);

        if (!$panier) {
            throw $this->createNotFoundException('Article du panier non trouvé');
        }

        if ($panier->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($panier);
        $this->entityManager->flush();

        $this->addFlash('success', 'Article retiré du panier');
        return $this->redirectToRoute('app_panier');
    }

    #[Route('/valider', name: 'app_panier_valider', methods: ['POST'])]
    public function valider(PanierRepository $panierRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $items = $panierRepository->findByUser($user);

        if (empty($items)) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('app_panier');
        }

        // Vérifier les stocks
        foreach ($items as $item) {
            if ($item->getQuantite() > $item->getLot()->getQuantite()) {
                $this->addFlash('error', 'Stock insuffisant pour ' . $item->getLot()->getName());
                return $this->redirectToRoute('app_panier');
            }
        }

        // Créer une commande pour chaque article
        $commandes = [];
        $totalGeneral = 0;

        foreach ($items as $item) {
            $commande = new Commande();
            $commande->setUser($user);
            $commande->setLot($item->getLot());
            $commande->setQuantite($item->getQuantite());
            $commande->setPrixUnitaire($item->getLot()->getPrix());
            $commande->setPrixTotal($item->getTotal());
            $commande->setStatut('en_attente');

            $this->entityManager->persist($commande);
            $commandes[] = $commande;
            $totalGeneral += $item->getTotal();

            // Mettre à jour le stock du lot
            $lot = $item->getLot();
            $nouvelleQuantite = $lot->getQuantite() - $item->getQuantite();

            if ($this->getParameter('kernel.environment') === 'dev') {
                error_log("DEBUG PANIER: Lot ID=" . $lot->getId() . ", Quantité actuelle=" . $lot->getQuantite() . ", Quantité commandée=" . $item->getQuantite() . ", Nouvelle quantité=" . $nouvelleQuantite);
            }

            if ($nouvelleQuantite <= 0) {
                $lot->setQuantite(0);
                $lot->setStatut('reserve');
                $lot->setReservePar($user);
                $lot->setReserveAt(new \DateTimeImmutable());

                if ($this->getParameter('kernel.environment') === 'dev') {
                    error_log("DEBUG PANIER: Stock atteint 0, marquage comme réservé");
                }
            } else {
                $lot->setQuantite($nouvelleQuantite);

                if ($this->getParameter('kernel.environment') === 'dev') {
                    error_log("DEBUG PANIER: Décrémentation de la quantité à " . $nouvelleQuantite);
                }
            }

            $this->entityManager->persist($lot);

            // Supprimer l'article du panier
            $this->entityManager->remove($item);
        }

        $this->entityManager->flush();

        // Envoyer l'email au client
        $this->sendCommandeConfirmation($user, $commandes, $totalGeneral);

        // Envoyer notification aux admins
        $this->sendAdminNotification($user, $commandes, $totalGeneral, $userRepository);

        $this->addFlash('success', 'Votre commande a été enregistrée ! Vous recevrez un email de confirmation avec les instructions de paiement.');

        return $this->redirectToRoute('app_mes_commandes');
    }

    private function sendCommandeConfirmation($user, array $commandes, float $total): void
    {
        // Générer l'URL du logo dynamiquement
        $baseUrl = $this->generateUrl('app_dash', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $logoUrl = str_replace('/dash', '/images/3tek-logo.png', $baseUrl);

        $email = (new Email())
            ->from(new Address('noreply@3tek-europe.com', '3Tek-Europe'))
            ->to($user->getEmail())
            ->subject('Confirmation de commande - ' . count($commandes) . ' article(s)')
            ->html(
                $this->twig->render('emails/commande_multiple_confirmation.html.twig', [
                    'user' => $user,
                    'commandes' => $commandes,
                    'total' => $total,
                    'logoUrl' => $logoUrl,
                ])
            );

        $this->mailer->send($email);
    }

    private function sendAdminNotification($user, array $commandes, float $total, UserRepository $userRepository): void
    {
        // Récupérer tous les admins
        $admins = $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();

        // Générer l'URL du logo dynamiquement
        $baseUrl = $this->generateUrl('app_dash', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $logoUrl = str_replace('/dash', '/images/3tek-logo.png', $baseUrl);

        foreach ($admins as $admin) {
            $email = (new Email())
                ->from(new Address('noreply@3tek-europe.com', '3Tek-Europe'))
                ->to($admin->getEmail())
                ->subject('Nouvelle commande de ' . $user->getName() . ' ' . $user->getLastname())
                ->html(
                    $this->twig->render('emails/admin_nouvelle_commande.html.twig', [
                        'admin' => $admin,
                        'client' => $user,
                        'commandes' => $commandes,
                        'total' => $total,
                        'logoUrl' => $logoUrl,
                    ])
                );

            $this->mailer->send($email);
        }
    }
}
