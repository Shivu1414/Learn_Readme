<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ManagerRegistry;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayoutTransactions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method SellerPayout|null find($id, $lockMode = null, $lockVersion = null)
 * @method SellerPayout|null findOneBy(array $criteria, array $orderBy = null)
 * @method SellerPayout[]    findAll()
 * @method SellerPayout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellerPayoutTransactionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, SellerPayoutTransactions::class);
        $this->container = $container;
    }

    public function getSellersTransactionsList ($params) 
    {
        $default_filter = [
            'page' => 1,
            'limit' => 10,
            'name' => '',
            'sort' => 'id',
            'order_by' => 'desc',
        ];

        $params = array_merge($default_filter, $params);
        
        $queryBuilder = $this->createQueryBuilder('o');
        if (isset($params['id']) && !empty($params['id'])) {
            $queryBuilder->andWhere('o.id = :id')->setParameter('id', $params['id']);
        }
        if (isset($params['batch_id']) && !empty($params['batch_id'])) {
            $queryBuilder->andWhere('o.batchId = :batch_id')->setParameter('batch_id', $params['batch_id']);
        }
        if (isset($params['sender_batch_id']) && !empty($params['sender_batch_id'])) {
            $queryBuilder->andWhere('o.senderBatchId = :senderBatchId')->setParameter('senderBatchId', $params['sender_batch_id']);
        }
        if (isset($params['company']) && !empty($params['company'])) {
            
            $queryBuilder->andWhere('o.company = :company')->setParameter('company', $params['company']);
        }
        
        if (isset($params['status']) && !empty($params['status'])) {
            $queryBuilder->andWhere('o.status IN (:status)')->setParameter('status', $params['status']);
        }

        if (isset($params['transaction_id']) && !empty($params['transaction_id'])) {
            $queryBuilder->andWhere('o.transactionId = :transaction_id')->setParameter('transaction_id', $params['transaction_id']);
        }
       
        if ((isset($params['start_date']) && !empty($params['start_date'])) && (isset($params['end_date']) && !empty($params['end_date']))) {
            $queryBuilder
                ->andWhere('o.createdAt >= :createdon')
                ->setParameter('createdon', strtotime($params['start_date']))
                ->andWhere('o.createdAt <= :expiredon')
                ->setParameter('expiredon', strtotime($params['end_date']));
        }

        $queryBuilder->orderBy('o.' . $params['sort'], $params['order_by']);
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
//    /**
//     * @return SellerPayout[] Returns an array of SellerPayout objects
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
    public function findOneBySomeField($value): ?SellerPayout
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
