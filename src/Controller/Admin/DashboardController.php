<?php

namespace App\Controller\Admin;

use App\Entity\Lot;
use App\Entity\Type;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Commande;
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
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
        //return $this->redirectToRoute('app_dash');
        //Touficreturn $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/images/3tek-logo.png" style="height: 30px; margin-right: 10px;"> 3Tek-Europe')
            ->setFaviconPath('/images/favicon.ico')
            ->setLocales(['fr' => '🇫🇷 Français'])
            ->setDefaultColorScheme('light');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('/css/admin-custom.css');
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
        
        yield MenuItem::section('Vue Client');
        yield MenuItem::linkToRoute('Dashboard Client', 'fa fa-shopping-cart', 'app_dash')
            ->setLinkTarget('_blank');
    }
}
