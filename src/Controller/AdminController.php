<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur a le rôle admin
        if (!$this->isGranted('ROLE_ADMIN')) {
            // Rediriger les utilisateurs normaux vers leur dashboard
            return $this->redirectToRoute('app_dash');
        }

        // L'utilisateur est admin, continuer vers l'interface d'administration
        return $this->redirectToRoute('admin');
    }
}
