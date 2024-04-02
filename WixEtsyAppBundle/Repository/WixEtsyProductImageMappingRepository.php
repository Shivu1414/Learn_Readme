<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Repository;

use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\WixEtsyProductImageMapping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WixEtsyProductImageMapping>
 *
 * @method WixEtsyProductImageMapping|null find($id, $lockMode = null, $lockVersion = null)
 * @method WixEtsyProductImageMapping|null findOneBy(array $criteria, array $orderBy = null)
 * @method WixEtsyProductImageMapping[]    findAll()
 * @method WixEtsyProductImageMapping[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WixEtsyProductImageMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WixEtsyProductImageMapping::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(WixEtsyProductImageMapping $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(WixEtsyProductImageMapping $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return WixEtsyProductImageMapping[] Returns an array of WixEtsyProductImageMapping objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WixEtsyProductImageMapping
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
