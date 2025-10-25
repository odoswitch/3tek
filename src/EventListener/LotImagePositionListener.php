<?php

namespace App\EventListener;

use App\Entity\Lot;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::prePersist, entity: Lot::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Lot::class)]
class LotImagePositionListener
{
    public function prePersist(Lot $lot, LifecycleEventArgs $event): void
    {
        $this->updateImagePositions($lot);
    }

    public function preUpdate(Lot $lot, LifecycleEventArgs $event): void
    {
        $this->updateImagePositions($lot);
    }

    private function updateImagePositions(Lot $lot): void
    {
        // Récupérer toutes les images et les trier par position
        $images = $lot->getImages()->toArray();
        
        // Trier les images par position
        usort($images, function($a, $b) {
            return $a->getPosition() <=> $b->getPosition();
        });
        
        // Réassigner les positions de manière séquentielle
        $position = 0;
        foreach ($images as $image) {
            $image->setPosition($position++);
        }
    }
}
