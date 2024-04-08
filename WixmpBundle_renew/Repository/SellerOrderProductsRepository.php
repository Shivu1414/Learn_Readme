<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrderProducts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SellerOrders|null find($id, $lockMode = null, $lockVersion = null)
 * @method SellerOrders|null findOneBy(array $criteria, array $orderBy = null)
 * @method SellerOrders[]    findAll()
 * @method SellerOrders[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellerOrderProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SellerOrderProducts::class);
    }

    /**
     * rempve records.
     */
    public function bulkRemove($params)
    {
        $em = $this->getEntityManager();
        $expr = $em->getExpressionBuilder();

        $queryBuilder = $this->createQueryBuilder('sop');
        $queryBuilder->delete();
        if (isset($params['ids'])) {
            $queryBuilder->andwhere('sop.id IN (:ids)')->setParameter('ids', $params['ids']);
        }
        if (isset($params['company'])) {
            // this table does not have company relation : use join
            $subQuery = $em->createQueryBuilder();
            $subQuery->select('p.id');
            $subQuery->from('Webkul\Modules\Bigcommerce\MarketplaceBundle\Entity\Products', 'p');
            $subQuery->andWhere('p.company = :company')
                ->setParameter('company', $params['company']);

            // subquery 2 for order check
            $subQuery2 = $em->createQueryBuilder();
            $subQuery2->select('o.id');
            $subQuery2->from('Webkul\Modules\Bigcommerce\MarketplaceBundle\Entity\SellerOrders', 'o');
            $subQuery2->andWhere('o.company = :company')
                ->setParameter('company', $params['company']);

            if (isset($params['seller'])) {
                $subQuery->andWhere('p.seller = :seller')
                    ->setParameter('seller', $params['seller']);
                $subQuery2->andWhere('o.seller = :seller')
                    ->setParameter('seller', $params['seller']);
                $queryBuilder->setParameter('seller', $params['seller']);
            }

            $queryBuilder->andwhere($queryBuilder->expr()->In('sop.Product', $subQuery->getDQL()).' OR '.$queryBuilder->expr()->In('sop.SellerOrder', $subQuery2->getDQL()));
            $queryBuilder->setParameter('company', $params['company']);
        }
        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

//    /**
//     * @return SellerOrders[] Returns an array of SellerOrders objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SellerOrders
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
