<?php

namespace App\EventListener;

use App\Entity\Commande;
use App\Service\LotLiberationServiceAmeliore;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;

#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Commande::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Commande::class)]
class CommandeDoctrineListener
{
    public function __construct(
        private LotLiberationServiceAmeliore $lotLiberationService,
        private EntityManagerInterface $entityManager
    ) {}

    public function postRemove(Commande $commande, PostRemoveEventArgs $event): void
    {
        error_log("DEBUG DOCTRINE LISTENER: Commande supprimée - ID=" . $commande->getId() . ", Statut=" . $commande->getStatut());

        $lot = $commande->getLot();

        if (!$lot) {
            error_log("DEBUG DOCTRINE LISTENER: Commande ID={$commande->getId()} n'a pas de lot associé.");
            return;
        }

        error_log("DEBUG DOCTRINE LISTENER: Libération automatique du lot ID={$lot->getId()} en cours...");

        // Toujours libérer le lot lors de la suppression
        $this->lotLiberationService->libererLot($lot);

        error_log("DEBUG DOCTRINE LISTENER: Lot ID={$lot->getId()} libéré automatiquement avec succès");
    }

    public function postUpdate(Commande $commande, PostUpdateEventArgs $event): void
    {
        error_log("DEBUG DOCTRINE LISTENER: Commande mise à jour - ID=" . $commande->getId() . ", Statut=" . $commande->getStatut());

        // Pour postUpdate, on ne peut pas détecter les changements facilement
        // On va simplement vérifier si la commande est annulée
        if ($commande->getStatut() === 'annulee') {
            $lot = $commande->getLot();

            if (!$lot) {
                error_log("DEBUG DOCTRINE LISTENER: Commande ID={$commande->getId()} n'a pas de lot associé.");
                return;
            }

            error_log("DEBUG DOCTRINE LISTENER: Commande annulée - libération du lot ID={$lot->getId()}");
            $this->lotLiberationService->libererLot($lot);
            error_log("DEBUG DOCTRINE LISTENER: Lot ID={$lot->getId()} libéré avec succès");
        }
    }
}
