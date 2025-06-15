<?php

namespace App\Controller;

use App\Repository\LotRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashController extends AbstractController
{
    #[Route('/dash', name: 'app_dash')]
    public function index(LotRepository $lot): Response
    {
        $data = $lot->findAll();
        //dd($data);
        return $this->render('dash1.html.twig', [

            'controller_name' => 'DashController',
            'data' => $data,
        ]);
    }
}
