<?php

namespace App\EventListener;

use App\Entity\Commande;
use App\Repository\FileAttenteRepository;
use App\Service\LotLiberationServiceAmeliore;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;

#[AsDoctrineListener(event: Events::preRemove)]
class CommandeDeleteListener
{
    public function __construct(
        private FileAttenteRepository $fileAttenteRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private LotLiberationServiceAmeliore $lotLiberationService
    ) {}

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Commande) {
            return;
        }

        $this->logger->info("DEBUG LISTENER: Suppression commande ID=" . $entity->getId() . ", Statut=" . $entity->getStatut());

        // Si la commande était en statut "reserve" ou "validee", libérer le lot
        if ($entity->getStatut() === 'reserve' || $entity->getStatut() === 'validee') {
            $this->logger->info("DEBUG LISTENER: Libération du lot en cours...");

            $lot = $entity->getLot();
            $this->lotLiberationService->libererLot($lot);

            $this->logger->info("DEBUG LISTENER: Lot libéré avec succès");
        } else {
            $this->logger->info("DEBUG LISTENER: Commande pas en statut réservé/validé, pas de libération");
        }
    }
}
