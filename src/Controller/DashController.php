<?php

namespace App\Controller;


use App\Repository\LotRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class DashController extends AbstractController
{
    #[Route('/dash', name: 'app_dash')]
    public function index(LotRepository $lot): Response
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
       //dd($data);
        return $this->render('dash1.html.twig', [

            'controller_name' => 'DashController',
            'data' => $data,
        ]);

       }
       
        
    }
}
