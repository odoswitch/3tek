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

    public function lotUserQuery($param){
        return $this->createQueryBuilder('l')
            ->leftJoin('l.images', 'images')
            ->addSelect('images')
            ->leftJoin('l.types', 'lt')
            ->addSelect('lt')
            ->leftJoin('l.cat', 'cat')
            ->addSelect('cat')
            ->innerJoin('App\Entity\User', 'u', 'WITH', 'u.id = :userId')
            ->innerJoin('u.categorie', 'uc')
            ->leftJoin('u.type', 'ut')
            ->where('uc = l.cat')
            ->andWhere('u.isVerified = 1')
            ->andWhere('ut MEMBER OF l.types OR u.type IS NULL')
            ->setParameter('userId', $param)
            ->orderBy('l.quantite', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->getQuery();
     }

    public function lotUser($param){
        return $this->lotUserQuery($param)->getResult();
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
