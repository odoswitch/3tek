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

    public function lotUser1($a){
       // $a = 2;
       $sql = 'SELECT * FROM lot INNER JOIN user on lot.id = user.lot_id WHERE user.id = '.$a ;
       // $sql = 'SELECT * FROM lot INNER JOIN user on lot.id = user.lot_id WHERE user.id = '.$a .'AND user.is_verified = 0';
        $con = $this->getEntityManager()->getConnection();
        $resultat = $con->query($sql);
        return $resultat->fetchAllAssociative();
    }

    public function lotUser($param){
        $sql = "SELECT * FROM `lot` l JOIN user_category uc ON uc.category_id = l.cat_id JOIN lot_type lt JOIN user u ON u.lot_id = lt.type_id AND lt.lot_id = l.id WHERE u.is_verified = 1 AND u.id = " .$param;
        //$sql ="SELECT *  FROM `lot` l  JOIN user u  ON l.id = u.lot_id JOIN lot_type lt ON lt.lot_id = u.lot_id JOIN user_category uc ON uc.user_id = u.id WHERE u.is_verified = 1 AND u.id =  " .$a ;
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
