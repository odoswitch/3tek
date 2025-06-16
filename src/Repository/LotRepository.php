<?php

namespace App\Repository;

use App\Entity\Lot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lot>
 */
class LotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lot::class);
    }

    public function lotUser($a){
       // $a = 2;
       $sql = 'SELECT * FROM lot INNER JOIN user on lot.id = user.lot_id WHERE user.id = '.$a ;
       // $sql = 'SELECT * FROM lot INNER JOIN user on lot.id = user.lot_id WHERE user.id = '.$a .'AND user.is_verified = 0';
        $con = $this->getEntityManager()->getConnection();
        $resultat = $con->query($sql);
        return $resultat->fetchAllAssociative();
    }



    //    public function findOneBySomeField($value): ?Lot
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
