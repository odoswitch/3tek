<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashController extends AbstractController
{
    #[Route('/dash', name: 'app_dash')]
    public function index(): Response
    {
        //return $this->render('dash.html.twig', [
        return $this->render('dash1.html.twig', [

            'controller_name' => 'DashController',
        ]);
    }
}
