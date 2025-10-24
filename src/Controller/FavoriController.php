<?php

namespace App\Controller;

use App\Entity\Favori;
use App\Repository\LotRepository;
use App\Repository\FavoriRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/favoris')]
class FavoriController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_favoris')]
    public function index(FavoriRepository $favoriRepository): Response
    {
        $user = $this->getUser();
        $favoris = $favoriRepository->findByUser($user);

        return $this->render('favori/index.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    #[Route('/toggle/{lotId}', name: 'app_favori_toggle')]
    public function toggle(int $lotId, LotRepository $lotRepository, FavoriRepository $favoriRepository): Response
    {
        $lot = $lotRepository->find($lotId);
        
        if (!$lot) {
            $this->addFlash('error', 'Lot introuvable');
            return $this->redirectToRoute('app_dash');
        }

        $user = $this->getUser();

        // Vérifier si le lot est déjà en favori
        $existingFavori = $this->entityManager->getRepository(Favori::class)->findOneBy([
            'user' => $user,
            'lot' => $lot
        ]);

        if ($existingFavori) {
            // Retirer des favoris
            $this->entityManager->remove($existingFavori);
            $this->entityManager->flush();
            $this->addFlash('success', 'Lot retiré des favoris');
        } else {
            // Ajouter aux favoris
            $favori = new Favori();
            $favori->setUser($user);
            $favori->setLot($lot);
            $this->entityManager->persist($favori);
            $this->entityManager->flush();
            $this->addFlash('success', 'Lot ajouté aux favoris');
        }

        // Rediriger vers la page précédente ou le dashboard
        $referer = $_SERVER['HTTP_REFERER'] ?? $this->generateUrl('app_dash');
        return $this->redirect($referer);
    }

    #[Route('/remove/{id}', name: 'app_favori_remove')]
    public function remove(Favori $favori): Response
    {
        if ($favori->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($favori);
        $this->entityManager->flush();

        $this->addFlash('success', 'Lot retiré des favoris');
        return $this->redirectToRoute('app_favoris');
    }
}
