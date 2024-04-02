<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Repository;

use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<Orders>
 *
 * @method Orders|null find($id, $lockMode = null, $lockVersion = null)
 * @method Orders|null findOneBy(array $criteria, array $orderBy = null)
 * @method Orders[]    findAll()
 * @method Orders[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrdersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, Orders::class);
        $this->container = $container;
    }

    public function getOrders($filter, $company)
    {  
        $default_filter = [
            'page' => 1,
            'limit' => 10,
            'name' => '',
            'sort' => 'id',
            'order_by' => 'desc',
        ];

        $filter = array_merge($default_filter, $filter); 
        $ordering = 'p.'.$filter['sort'].' '.$filter['order_by'];
     
        if (isset($filter['fullordering']) && !empty($filter['fullordering'])) {
            $ordering = 'p.'.$filter['fullordering'];
        }
        $query = $this->createQueryBuilder('p')
            ->where('p.company = :company')
            ->setParameter('company', $company)
            // ->andWhere('p.name LIKE :name')
            // ->setParameter('name', "%".$filter['name']."%")
            /* ->orderBy('p.' . $filter['sort'], $filter['order_by']) */
            ->add('orderBy', $ordering);

        if (isset($filter['fields']) && !empty($filter['fields'])) {
            $fields = $filter['fields'];
            if (!is_array($filter['fields'])) {
                $fields = explode(',', $filter['fields']);
            }
            $query->select($fields);
        }
        if (isset($filter['ids']) && !empty($filter['ids'])) {
            $query->andWhere('p.id IN(:ids)')->setParameter('ids', $filter['ids']);
        }
        if (isset($filter['id']) && !empty($filter['id'])) {
            $query->andWhere('p.id = :id')->setParameter('id', $filter['id']);
        }

        if (isset($filter['receipt_id']) && !empty($filter['receipt_id'])) {
            $query->andWhere('p.receipt_id = :receipt_id')->setParameter('receipt_id', $filter['receipt_id']);
        }
        
        if (isset($filter['wix_order_no']) && !empty($filter['wix_order_no'])) {
            $query->andWhere('p.wix_order_no = :wix_order_no')->setParameter('wix_order_no', $filter['wix_order_no']);
        }
        
        if (isset($filter['status']) && !empty($filter['status'])) {
            $query->andWhere('p.sync_status = :status')->setParameter('status', $filter['status']);
        }
        if (isset($filter['statuses']) && !empty($filter['statuses'])) {
            $query->andWhere('p.sync_status IN(:statuses)')->setParameter('statuses', $filter['statuses']);
        }
        
        if (isset($filter['shipment']) && !empty($filter['shipment'])) {
            $query->andWhere('p.is_shipped = :is_shipped')->setParameter('is_shipped', $filter['shipment']);
        }
        
        if(isset($filter['order']) && !empty($filter['order'])) {
            $query->andwhere('p.order_status = :order_status')->setParameter('order_status', $filter['order']);
        }
        if (isset($filter['get_all_results'])) {
            return $query->getQuery()->getResult();
        } else {
            return $this->createPagination($query->getQuery(), $filter);
        }
        
    }
    
    private function createPagination(Query $query, $params)
    {
        $paginator = $this->container->get('knp_paginator');

        return $paginator->paginate($query, $params['page'], $params['items_per_page']);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Orders $entity, bool $flush = true): void
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
    public function remove(Orders $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Orders[] Returns an array of Orders objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Orders
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
