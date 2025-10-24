<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionTimeoutListener
{
    private const TIMEOUT = 28800; // 8 heures en secondes (pour l'espace client uniquement)
    
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        // Ignorer les routes publiques et admin
        $route = $request->attributes->get('_route');
        $publicRoutes = ['app_login', 'app_register', 'app_logout', 'app_forgot_password', 'app_reset_password'];
        
        if (in_array($route, $publicRoutes)) {
            return;
        }
        
        // Ignorer toutes les routes admin (EasyAdmin)
        if (str_starts_with($route, 'admin') || str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }

        // Vérifier si l'utilisateur est connecté
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            return;
        }

        $now = time();
        $lastActivity = $session->get('last_activity', $now);

        // Si plus de 8 heures d'inactivité
        if ($now - $lastActivity > self::TIMEOUT) {
            // Déconnecter l'utilisateur
            $this->tokenStorage->setToken(null);
            $session->invalidate();

            // Rediriger vers la page de connexion avec un message
            $session->getFlashBag()->add('warning', 'Votre session a expiré après 8 heures d\'inactivité. Veuillez vous reconnecter.');
            
            $response = new RedirectResponse($this->urlGenerator->generate('app_login'));
            $event->setResponse($response);
            return;
        }

        // Mettre à jour le timestamp de dernière activité
        $session->set('last_activity', $now);
    }
}
