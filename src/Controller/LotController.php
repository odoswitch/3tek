<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Repository\LotRepository;
use App\Repository\FavoriRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LotController extends AbstractController
{
    #[Route('/lot/{id}', name: 'app_lot_view')]
    public function view(Lot $lot, FavoriRepository $favoriRepository): Response
    {
        $isFavorite = false;
        if ($this->getUser()) {
            $isFavorite = $favoriRepository->isFavorite($this->getUser(), $lot);
        }

        return $this->render('lot/view.html.twig', [
            'lot' => $lot,
            'isFavorite' => $isFavorite,
        ]);
    }

    #[Route('/lots', name: 'app_lots_list')]
    public function list(LotRepository $lotRepository): Response
    {
        $user = $this->getUser();

        // Admin voit tous les lots
        if ($this->isGranted('ROLE_ADMIN')) {
            $lots = $lotRepository->findAll();
        } else {
            // Utilisateur normal : filtrer par catégorie et type
            if (!$user || $user->getCategorie()->isEmpty() || !$user->getType()) {
                // Si pas de catégorie ou type, aucun lot
                $lots = [];
            } else {
                // Filtrer les lots selon les catégories et le type de l'utilisateur
                // Montrer tous les lots, même ceux avec quantité = 0 (réservés)
                $lots = $lotRepository->createQueryBuilder('l')
                    ->leftJoin('l.images', 'images')
                    ->addSelect('images')
                    ->leftJoin('l.types', 'lt')
                    ->addSelect('lt')
                    ->where('l.cat IN (:categories)')
                    ->andWhere(':userType MEMBER OF l.types')
                    ->setParameter('categories', $user->getCategorie())
                    ->setParameter('userType', $user->getType())
                    ->orderBy('l.quantite', 'DESC')
                    ->addOrderBy('l.id', 'DESC')
                    ->getQuery()
                    ->getResult();
            }
        }

        return $this->render('lot/list.html.twig', [
            'lots' => $lots,
        ]);
    }
}
