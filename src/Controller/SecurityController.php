<?php

namespace App\Controller;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            //Switch entre le Dashbord user et admi 

            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin');
            } else {
                return $this->redirectToRoute('app_dash');
            }
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth-login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/test', name: 'app_test')]
    public function test(MailerInterface $mailer): Response
    {
        $email = (new Email())->from('test@odoip.fr')
        ->to('info@odoip.fr')
        ->cc('congocrei2000@gmail.com')
        ->replyTo('info@odoip.fr')
        ->subject('NGAMBA TEST MAIl')
        ->text('Bonjour si vous avez reçu ce message cela veut dire tout est ok pour vous sur le partir dsn')
        ->html('<h2>Bonjour si vous avez reçu ce message cela veut dire tout est ok pour vous sur le partir dsn</h2>');
        $mailer->send($email);
        return $this->redirectToRoute('app_login');
    }

    
}
