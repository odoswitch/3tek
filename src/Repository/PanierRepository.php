<?php

namespace App\Repository;

use App\Entity\Panier;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.lot', 'l')
            ->addSelect('l')
            ->leftJoin('l.images', 'images')
            ->addSelect('images')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalByUser(User $user): float
    {
        $items = $this->findByUser($user);
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item->getTotal();
        }
        
        return $total;
    }
}
