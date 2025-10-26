<?php

namespace App\Controller\Admin;

use App\Entity\Lot;
use App\Entity\Type;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Commande;
use App\Entity\EmailLog;
use App\Entity\FileAttente;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
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
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/images/3tek-logo.png" style="height: 40px; margin-right: 10px;"> <span style="color: #0066cc; font-weight: 700;">3Tek-Europe</span>')
            ->setFaviconPath('/images/favicon.ico')
            ->setLocales(['fr' => '🇫🇷 Français'])
            ->setDefaultColorScheme('light');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('/css/admin-custom.css')
            ->addHtmlContentToHead('<style>
                /* 3Tek theme colors */
                :root {
                    --color-primary: #0066cc !important;
                    --color-primary-dark: #0052a3 !important;
                    --color-success: #10b981 !important;
                    --color-warning: #f59e0b !important;
                    --color-danger: #ef4444 !important;
                }
                
                /* Sidebar styling */
                .sidebar {
                    background: linear-gradient(180deg, #0066cc 0%, #0052a3 100%) !important;
                }
                
                .sidebar .menu-item a {
                    color: white !important;
                    font-weight: 500 !important;
                }
                
                .sidebar .menu-item a:hover {
                    background: rgba(255, 255, 255, 0.15) !important;
                }
                
                .sidebar .menu-item.active a {
                    background: rgba(255, 255, 255, 0.25) !important;
                    font-weight: 600 !important;
                }
                
                .sidebar .menu-header {
                    color: white !important;
                    font-weight: 700 !important;
                    opacity: 0.9 !important;
                }
                
                .sidebar i,
                .sidebar svg {
                    color: white !important;
                }
                
                /* Buttons */
                .btn-primary {
                    background-color: #0066cc !important;
                    border-color: #0066cc !important;
                }
                
                .btn-primary:hover {
                    background-color: #0052a3 !important;
                    border-color: #0052a3 !important;
                }
                
                /* Tables */
                .table thead th {
                    background-color: #0066cc !important;
                    color: white !important;
                    font-weight: 600 !important;
                }
                
                .table tbody td {
                    color: #1f2937 !important;
                    font-size: 14px !important;
                }
                
                .table tbody tr:hover {
                    background-color: #f0f9ff !important;
                }
                
                /* Links in table */
                .table a {
                    color: #0066cc !important;
                    text-decoration: none !important;
                }
                
                .table a:hover {
                    color: #0052a3 !important;
                    text-decoration: underline !important;
                }
                
                /* Page title */
                .content-header-title {
                    color: #1f2937 !important;
                    font-weight: 700 !important;
                }
                
                /* Form controls */
                .form-control:focus,
                .form-select:focus {
                    border-color: #0066cc !important;
                    box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25) !important;
                }
                
                /* Pagination */
                .page-link {
                    color: #0066cc !important;
                }
                
                .page-item.active .page-link {
                    background-color: #0066cc !important;
                    border-color: #0066cc !important;
                }
                
                /* Badges */
                .badge-primary {
                    background-color: #0066cc !important;
                }
            </style>');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard Admin', 'fa fa-home');

        yield MenuItem::section('Gestion');
        yield MenuItem::linkToCrud('Liste clients', 'fa-solid fa-user', User::class);
        yield MenuItem::linkToCrud('Catégories', 'fas fa-list', Category::class);
        yield MenuItem::linkToCrud('Lots', 'fa-solid fa-gift', Lot::class);
        yield MenuItem::linkToCrud('Types Client', 'fa-solid fa-face-smile', Type::class);

        yield MenuItem::section('Commandes');
        yield MenuItem::linkToCrud('Toutes les commandes', 'fa fa-shopping-bag', Commande::class);
        yield MenuItem::linkToCrud('Files d\'attente', 'fa fa-clock', FileAttente::class);

        yield MenuItem::section('Système');
        yield MenuItem::linkToCrud('Logs Emails', 'fa fa-envelope', EmailLog::class);

        yield MenuItem::section('Vue Client');
        yield MenuItem::linkToRoute('Dashboard Client', 'fa fa-shopping-cart', 'app_dash')
            ->setLinkTarget('_blank');
    }
}
