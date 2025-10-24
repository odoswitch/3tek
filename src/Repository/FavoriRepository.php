<?php

namespace App\Repository;

use App\Entity\Favori;
use App\Entity\User;
use App\Entity\Lot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favori>
 */
class FavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favori::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.lot', 'l')
            ->addSelect('l')
            ->leftJoin('l.images', 'images')
            ->addSelect('images')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isFavorite(User $user, Lot $lot): bool
    {
        $result = $this->findOneBy([
            'user' => $user,
            'lot' => $lot
        ]);

        return $result !== null;
    }
}
