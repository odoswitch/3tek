<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Repository\LotRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class DashController extends AbstractController
{
    #[Route('/dash', name: 'app_dash')]
    public function index(LotRepository $lot, Lot $l): Response
    {
        $user = $this->getUser()->getid();

        if($this->isGranted('ROLE_ADMIN')){
            $data = $lot->findAll();
            return $this->render('dash1.html.twig', [

                'controller_name' => 'DashController',
                'data' => $data,
            ]);


       }else{
        $data = $lot->lotUser($user);
       
        return $this->render('dash1.html.twig', [

            'controller_name' => 'DashController',
            'data' => $data,
        ]);

       }
       
        
    }
}
