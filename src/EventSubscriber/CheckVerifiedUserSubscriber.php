<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\HttpFoundation\RequestStack;

class CheckVerifiedUserSubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;

    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            // Store email in session for display on error page
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $request->getSession()->getFlashBag()->add(
                    'verify_email_error',
                    'Votre compte n\'est pas encore activé. Veuillez vérifier votre email et cliquer sur le lien de confirmation.'
                );
            }

            throw new CustomUserMessageAuthenticationException(
                'Votre compte n\'est pas encore activé. Veuillez vérifier votre email et cliquer sur le lien de confirmation.'
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', -10],
        ];
    }
}
