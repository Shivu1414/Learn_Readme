<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ManagerRegistry;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method SellerOrders|null find($id, $lockMode = null, $lockVersion = null)
 * @method SellerOrders|null findOneBy(array $criteria, array $orderBy = null)
 * @method SellerOrders[]    findAll()
 * @method SellerOrders[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellerOrdersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, SellerOrders::class);
        $this->container = $container;
    }

    /**
     * @return StoreOrders[] Returns an array of StoreOrders objects
     */
    public function getOrders($params)
    {
        $queryBuilder = $this->createQueryBuilder('store_order');
        if (isset($params['count'])) {
            $queryBuilder->select('count(store_order.id)');
        }

        if (isset($params['company']) && !empty($params['company'])) {
            $queryBuilder->andWhere('store_order.company = :company')->setParameter('company', $params['company']);
        }

        if (isset($params['seller']) && !empty($params['seller'])) {
            $queryBuilder->andWhere('store_order.seller = :seller')->setParameter('seller', $params['seller']);
        }

        if (isset($params['order_id']) && !empty($params['order_id'])) {
            $queryBuilder->andWhere('store_order.id = :order_id')->setParameter('order_id', $params['order_id']);
        }

        if (isset($params['store_order_id']) && !empty($params['store_order_id'])) {
            $queryBuilder->andWhere('store_order.storeOrderId = :order_id')->setParameter('order_id', $params['store_order_id']);
        }

        if (isset($params['store_order_no']) && !empty($params['store_order_no'])) {
            $queryBuilder->andWhere('store_order.storeOrderNo = :store_order_no')->setParameter('store_order_no', $params['store_order_no']);
        }

        if (isset($params['parent_only']) && $params['parent_only'] == 'N') {
            $queryBuilder->andWhere('store_order.isParent = :parent_only')->setParameter('parent_only', 'N');
        }

        if (isset($params['customer_name']) && !empty($params['customer_name'])) {
            $queryBuilder->andWhere('store_order.customerName = :customerName')->setParameter('customer_name', $params['customer_name']);
        }
        
        if (isset($params['seller_status']) && $params['seller_status'] != "") {
            $queryBuilder->andWhere('store_order.sellerStatus IN(:seller_status)')->setParameter('seller_status', $params['seller_status']);

        }
        elseif (isset($params['order_status_not']) && !isset($params['include_incomplete_orders']) ) {
            $queryBuilder->andWhere('store_order.sellerStatus NOT IN(:order_status_not)')->setParameter('order_status_not', $params['order_status_not']);  
        }

        if (isset($params['seller_fullfillment_status']) && $params['seller_fullfillment_status'] == 1) {
            $queryBuilder->andWhere('store_order.sellerFullfillmentStatus =:seller_fullfillment_status')->setParameter('seller_fullfillment_status', $params['seller_fullfillment_status']);
        }

        if (isset($params['fullfillment_status']) && $params['fullfillment_status'] != "") {
            $queryBuilder->andWhere('store_order.fullfillmentStatus IN(:fullfillment_status)')->setParameter('fullfillment_status', $params['fullfillment_status']);
        }

        // time 
        if ((isset($params['start_date']) && !empty($params['start_date'])) && (isset($params['end_date']) && !empty($params['end_date']))) {
            $queryBuilder
            ->andWhere('store_order.createdAt >= :createdon')
            ->setParameter('createdon', strtotime($params['start_date']) ? strtotime($params['start_date']) :$params['start_date'])
            ->andWhere('store_order.createdAt <= :expiredon')
            ->setParameter('expiredon', strtotime($params['end_date']) ? strtotime($params['end_date']) : $params['end_date']);
        }
        // ordering
        $ordering = 'store_order.'.$params['sort'].' '.$params['order_by'];
        if (isset($params['fullordering']) && !empty($params['fullordering'])) {
            $ordering = 'store_order.'.$params['fullordering'];
        }
        $queryBuilder->add('orderBy', $ordering);
        
        $query = $queryBuilder->getQuery();
  
        if (isset($params['get_single_result'])) {
            return $query->getOneOrNullResult();
        } elseif (isset($params['get_all_results'])) {
            return $query->getResult();
        } else {
            return $this->createPagination($query, $params);
        }
    }

    private function createPagination(Query $query, $params)
    {
        $paginator = $this->container->get('knp_paginator');

        return $paginator->paginate($query, $params['page'], $params['items_per_page']);
    }
    /**
     * rempve records 
     */
    public function bulkRemove($params)
    {
        $queryBuilder = $this->createQueryBuilder('so');
        $queryBuilder->delete();
        if (isset($params['ids'])) {
            $queryBuilder->andwhere('so.id IN (:ids)')->setParameter('ids', $params['ids']);
        }
        if (isset($params['company'])) {            
            $queryBuilder->andwhere('so.company = :company')->setParameter('company', $params['company']);
        } 
        if (isset($params['seller'])) {            
            $queryBuilder->andwhere('so.seller = :seller')->setParameter('seller', $params['seller']);
        } 
        $query = $queryBuilder->getQuery();   
       
        return $query->execute();
        
        
    }

    public function getOrdersByIds($orderIds = [])
    {
        $queryBuilder = $this->createQueryBuilder('so');
        $queryBuilder->andwhere('so.id IN (:ids)')->setParameter('ids', $orderIds);
        $query = $queryBuilder->getQuery();
        return $query->getResult();
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
