<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Doctrine\ORM\Query;
use Psr\Container\ContainerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method SellerPlan|null find($id, $lockMode = null, $lockVersion = null)
 * @method SellerPlan|null findOneBy(array $criteria, array $orderBy = null)
 * @method SellerPlan[]    findAll()
 * @method SellerPlan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellerPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, SellerPlan::class);
        $this->container = $container;
    }

    public function getPlans($params) {
        $queryBuilder = $this->createQueryBuilder('plan');
        $queryBuilder->orderBy('plan.'.$params['sort'], $params['order_by']);

        if(isset($params['company']) && $params['company']) {
            $queryBuilder->where('plan.company = :company')->setParameter('company', $params['company']);
        }

        if(isset($params['interval']) && !empty($params['interval'])) {
            $queryBuilder->andwhere('plan.intervalType = :interval')->setParameter('interval', $params['interval']);
        }

        if(isset($params['code']) && !empty($params['code'])) {
            $queryBuilder->andwhere('plan.code = :code')->setParameter('code', $params['code']);
        }

        if(isset($params['status']) && !empty($params['status']) && is_array($params['status'])) {
            $queryBuilder->andWhere('plan.status IN (:status)')->setParameter('status', $params['status']);
        } elseif(isset($params['status']) && !empty($params['status'])) {
            $queryBuilder->andWhere('plan.status = :status')->setParameter('status', $params['status']);
        }

        if(isset($params['plan']) && !empty($params['plan'])) {
            $queryBuilder->andWhere('plan.plan like :plan')->setParameter('plan', "%".$params['plan']."%");
        }
        // intervalType
        $query = $queryBuilder->getQuery();

        return $this->createPagination($query, $params);
    }

    private function createPagination(Query $query, $params)
    {
        $paginator = $this->container->get('knp_paginator');
        return $paginator->paginate($query, $params['page'],$params['items_per_page']);
    }
    /**
     * rempve records 
     */
    public function bulkRemove($params)
    {
        $queryBuilder = $this->createQueryBuilder('sp');
        $queryBuilder->delete();
        if (isset($params['ids'])) {
            $queryBuilder->andwhere('sp.id IN (:ids)')->setParameter('ids', $params['ids']);
        }
        if (isset($params['company'])) {
            $queryBuilder->andwhere('sp.company = :company')->setParameter('company', $params['company']);
        }        
        $query = $queryBuilder->getQuery();
        return $query->execute();
        
    }

//    /**
//     * @return SellerPlan[] Returns an array of SellerPlan objects
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
    public function findOneBySomeField($value): ?SellerPlan
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
