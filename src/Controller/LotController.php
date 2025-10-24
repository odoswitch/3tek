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
        $lots = $lotRepository->findAll();
        
        return $this->render('lot/list.html.twig', [
            'lots' => $lots,
        ]);
    }
}
