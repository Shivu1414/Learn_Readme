<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Webkul\Modules\Wix\WixmpBundle\Entity\PayoutCommissions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PayoutCommissions>
 *
 * @method PayoutCommissions|null find($id, $lockMode = null, $lockVersion = null)
 * @method PayoutCommissions|null findOneBy(array $criteria, array $orderBy = null)
 * @method PayoutCommissions[]    findAll()
 * @method PayoutCommissions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PayoutCommissionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayoutCommissions::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PayoutCommissions $entity, bool $flush = true): void
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
    public function remove(PayoutCommissions $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return PayoutCommissions[] Returns an array of PayoutCommissions objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PayoutCommissions
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
