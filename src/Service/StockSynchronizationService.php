<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\CommandeLigne;
use App\Entity\Lot;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class StockSynchronizationService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Synchronise le stock lors de la création d'une commande
     */
    public function synchronizeStockOnCommandeCreation(Commande $commande): void
    {
        $this->logger->info('SYNC STOCK: Début synchronisation pour commande ID=' . $commande->getId());

        // Pour les commandes avec lignes multiples
        if ($commande->getLignes()->count() > 0) {
            foreach ($commande->getLignes() as $ligne) {
                $this->updateLotStock($ligne->getLot(), $ligne->getQuantite(), $commande->getStatut());
            }
        }
        // Pour les commandes simples (compatibilité)
        elseif ($commande->getLot()) {
            $this->updateLotStock($commande->getLot(), $commande->getQuantite(), $commande->getStatut());
        }

        $this->entityManager->flush();
        $this->logger->info('SYNC STOCK: Synchronisation terminée pour commande ID=' . $commande->getId());
    }

    /**
     * Synchronise le stock lors du changement de statut d'une commande
     */
    public function synchronizeStockOnStatusChange(Commande $commande, string $ancienStatut): void
    {
        $this->logger->info('SYNC STOCK: Changement statut commande ID=' . $commande->getId() . ' de ' . $ancienStatut . ' vers ' . $commande->getStatut());

        // Annulation : restaurer le stock
        if ($commande->getStatut() === 'annulee' && $ancienStatut !== 'annulee') {
            $this->restoreStock($commande);
        }
        // Validation : confirmer la réduction de stock
        elseif ($commande->getStatut() === 'validee' && $ancienStatut !== 'validee') {
            $this->confirmStockReduction($commande);
        }
        // Réservation : réserver le stock
        elseif ($commande->getStatut() === 'reserve' && $ancienStatut !== 'reserve') {
            $this->reserveStock($commande);
        }

        $this->entityManager->flush();
    }

    /**
     * Met à jour le stock d'un lot selon le statut de la commande
     */
    private function updateLotStock(Lot $lot, int $quantiteCommande, string $statutCommande): void
    {
        $this->logger->info('SYNC STOCK: Lot ID=' . $lot->getId() . ', Quantité actuelle=' . $lot->getQuantite() . ', Quantité commandée=' . $quantiteCommande . ', Statut=' . $statutCommande);

        switch ($statutCommande) {
            case 'en_attente':
                // En attente : réduire le stock visible mais garder disponible
                $nouvelleQuantite = max(0, $lot->getQuantite() - $quantiteCommande);
                $lot->setQuantite($nouvelleQuantite);

                if ($nouvelleQuantite <= 0) {
                    $lot->setStatut('reserve');
                    $lot->setReservePar($commande->getUser());
                    $lot->setReserveAt(new \DateTimeImmutable());
                }
                break;

            case 'validee':
                // Validée : confirmer la réduction de stock
                $nouvelleQuantite = max(0, $lot->getQuantite() - $quantiteCommande);
                $lot->setQuantite($nouvelleQuantite);

                if ($nouvelleQuantite <= 0) {
                    $lot->setStatut('vendu');
                } else {
                    $lot->setStatut('disponible');
                }
                break;

            case 'reserve':
                // Réservée : réserver le lot
                $lot->setStatut('reserve');
                $lot->setReservePar($commande->getUser());
                $lot->setReserveAt(new \DateTimeImmutable());
                $lot->setQuantite(max(0, $lot->getQuantite() - $quantiteCommande));
                break;

            case 'annulee':
                // Annulée : ne pas toucher au stock (sera géré par restoreStock)
                break;
        }

        $this->entityManager->persist($lot);
        $this->logger->info('SYNC STOCK: Lot ID=' . $lot->getId() . ' mis à jour, nouvelle quantité=' . $lot->getQuantite() . ', statut=' . $lot->getStatut());
    }

    /**
     * Restaure le stock lors de l'annulation d'une commande
     */
    private function restoreStock(Commande $commande): void
    {
        $this->logger->info('SYNC STOCK: Restauration stock pour commande annulée ID=' . $commande->getId());

        // Pour les commandes avec lignes multiples
        if ($commande->getLignes()->count() > 0) {
            foreach ($commande->getLignes() as $ligne) {
                $lot = $ligne->getLot();
                $quantiteRestoree = $ligne->getQuantite();

                $lot->setQuantite($lot->getQuantite() + $quantiteRestoree);

                // Si le lot était vendu, le remettre disponible
                if ($lot->getStatut() === 'vendu') {
                    $lot->setStatut('disponible');
                }

                $this->entityManager->persist($lot);
                $this->logger->info('SYNC STOCK: Stock restauré pour lot ID=' . $lot->getId() . ', quantité restaurée=' . $quantiteRestoree);
            }
        }
        // Pour les commandes simples
        elseif ($commande->getLot()) {
            $lot = $commande->getLot();
            $quantiteRestoree = $commande->getQuantite();

            $lot->setQuantite($lot->getQuantite() + $quantiteRestoree);

            if ($lot->getStatut() === 'vendu') {
                $lot->setStatut('disponible');
            }

            $this->entityManager->persist($lot);
            $this->logger->info('SYNC STOCK: Stock restauré pour lot ID=' . $lot->getId() . ', quantité restaurée=' . $quantiteRestoree);
        }
    }

    /**
     * Confirme la réduction de stock lors de la validation d'une commande
     */
    private function confirmStockReduction(Commande $commande): void
    {
        $this->logger->info('SYNC STOCK: Confirmation réduction stock pour commande validée ID=' . $commande->getId());

        // Pour les commandes avec lignes multiples
        if ($commande->getLignes()->count() > 0) {
            foreach ($commande->getLignes() as $ligne) {
                $lot = $ligne->getLot();

                // Le stock a déjà été réduit lors de la création, on confirme juste le statut
                if ($lot->getQuantite() <= 0) {
                    $lot->setStatut('vendu');
                } else {
                    $lot->setStatut('disponible');
                }

                $this->entityManager->persist($lot);
            }
        }
        // Pour les commandes simples
        elseif ($commande->getLot()) {
            $lot = $commande->getLot();

            if ($lot->getQuantite() <= 0) {
                $lot->setStatut('vendu');
            } else {
                $lot->setStatut('disponible');
            }

            $this->entityManager->persist($lot);
        }
    }

    /**
     * Réserve le stock lors de la réservation d'une commande
     */
    private function reserveStock(Commande $commande): void
    {
        $this->logger->info('SYNC STOCK: Réservation stock pour commande ID=' . $commande->getId());

        // Pour les commandes avec lignes multiples
        if ($commande->getLignes()->count() > 0) {
            foreach ($commande->getLignes() as $ligne) {
                $lot = $ligne->getLot();

                $lot->setStatut('reserve');
                $lot->setReservePar($commande->getUser());
                $lot->setReserveAt(new \DateTimeImmutable());

                $this->entityManager->persist($lot);
            }
        }
        // Pour les commandes simples
        elseif ($commande->getLot()) {
            $lot = $commande->getLot();

            $lot->setStatut('reserve');
            $lot->setReservePar($commande->getUser());
            $lot->setReserveAt(new \DateTimeImmutable());

            $this->entityManager->persist($lot);
        }
    }

    /**
     * Vérifie la cohérence entre les commandes et le stock
     */
    public function checkStockConsistency(): array
    {
        $inconsistencies = [];

        // Vérifier les commandes en attente
        $commandesEnAttente = $this->entityManager->getRepository(Commande::class)
            ->findBy(['statut' => 'en_attente']);

        foreach ($commandesEnAttente as $commande) {
            if ($commande->getLignes()->count() > 0) {
                foreach ($commande->getLignes() as $ligne) {
                    $lot = $ligne->getLot();
                    if ($lot->getQuantite() < 0) {
                        $inconsistencies[] = [
                            'type' => 'stock_negatif',
                            'commande_id' => $commande->getId(),
                            'lot_id' => $lot->getId(),
                            'quantite_stock' => $lot->getQuantite(),
                            'quantite_commande' => $ligne->getQuantite()
                        ];
                    }
                }
            } elseif ($commande->getLot()) {
                $lot = $commande->getLot();
                if ($lot->getQuantite() < 0) {
                    $inconsistencies[] = [
                        'type' => 'stock_negatif',
                        'commande_id' => $commande->getId(),
                        'lot_id' => $lot->getId(),
                        'quantite_stock' => $lot->getQuantite(),
                        'quantite_commande' => $commande->getQuantite()
                    ];
                }
            }
        }

        return $inconsistencies;
    }

    /**
     * Corrige les incohérences de stock
     */
    public function fixStockInconsistencies(): int
    {
        $inconsistencies = $this->checkStockConsistency();
        $fixed = 0;

        foreach ($inconsistencies as $inconsistency) {
            if ($inconsistency['type'] === 'stock_negatif') {
                $lot = $this->entityManager->getRepository(Lot::class)
                    ->find($inconsistency['lot_id']);

                if ($lot) {
                    // Remettre le stock à 0 minimum
                    $lot->setQuantite(max(0, $lot->getQuantite()));
                    $this->entityManager->persist($lot);
                    $fixed++;
                }
            }
        }

        if ($fixed > 0) {
            $this->entityManager->flush();
        }

        return $fixed;
    }
}

