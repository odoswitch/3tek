<?php

namespace App\EventListener;

use App\Entity\Lot;
use App\Service\LotNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, entity: Lot::class)]
class LotCreatedListener
{
    public function __construct(
        private LotNotificationService $notificationService
    ) {
    }

    public function postPersist(Lot $lot, LifecycleEventArgs $event): void
    {
        // Envoyer les notifications par email aux utilisateurs concernés
        $this->notificationService->notifyUsersAboutNewLot($lot);
    }
}
