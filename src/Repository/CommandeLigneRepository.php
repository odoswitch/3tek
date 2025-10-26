<?php

namespace App\Repository;

use App\Entity\CommandeLigne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeLigne>
 */
class CommandeLigneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeLigne::class);
    }

    /**
     * Trouve toutes les lignes d'une commande
     */
    public function findByCommande($commande): array
    {
        return $this->createQueryBuilder('cl')
            ->andWhere('cl.commande = :commande')
            ->setParameter('commande', $commande)
            ->orderBy('cl.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total d'une commande
     */
    public function getTotalCommande($commande): float
    {
        $result = $this->createQueryBuilder('cl')
            ->select('SUM(cl.prixTotal)')
            ->andWhere('cl.commande = :commande')
            ->setParameter('commande', $commande)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?: 0);
    }
}

