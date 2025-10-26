<?php

namespace App\Repository;

use App\Entity\FileAttente;
use App\Entity\Lot;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileAttente>
 */
class FileAttenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileAttente::class);
    }

    /**
     * Trouve la position suivante dans la file d'attente pour un lot
     */
    public function getNextPosition(Lot $lot): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('MAX(f.position)')
            ->where('f.lot = :lot')
            ->andWhere('f.statut = :statut')
            ->setParameter('lot', $lot)
            ->setParameter('statut', 'en_attente')
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }

    /**
     * Trouve la file d'attente pour un lot donné
     */
    public function findByLot(Lot $lot): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.lot = :lot')
            ->andWhere('f.statut = :statut')
            ->setParameter('lot', $lot)
            ->setParameter('statut', 'en_attente')
            ->orderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur est déjà dans la file d'attente pour un lot
     */
    public function isUserInQueue(Lot $lot, User $user): bool
    {
        $count = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.lot = :lot')
            ->andWhere('f.user = :user')
            ->andWhere('f.statut = :statut')
            ->setParameter('lot', $lot)
            ->setParameter('user', $user)
            ->setParameter('statut', 'en_attente')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Trouve le premier utilisateur en attente pour un lot
     */
    public function findFirstInQueue(Lot $lot): ?FileAttente
    {
        return $this->createQueryBuilder('f')
            ->where('f.lot = :lot')
            ->andWhere('f.statut = :statut')
            ->setParameter('lot', $lot)
            ->setParameter('statut', 'en_attente')
            ->orderBy('f.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime l'utilisateur de la file d'attente
     */
    public function removeFromQueue(FileAttente $fileAttente): void
    {
        $this->getEntityManager()->remove($fileAttente);
        $this->getEntityManager()->flush();
    }

    /**
     * Trouve la dernière position dans la file d'attente pour un lot
     */
    public function getLastPositionForLot(Lot $lot): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('MAX(f.position)')
            ->where('f.lot = :lot')
            ->andWhere('f.statut = :statut')
            ->setParameter('lot', $lot)
            ->setParameter('statut', 'en_attente')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Trouve les files d'attente d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', 'en_attente')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
