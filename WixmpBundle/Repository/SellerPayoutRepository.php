<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ManagerRegistry;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method SellerPayout|null find($id, $lockMode = null, $lockVersion = null)
 * @method SellerPayout|null findOneBy(array $criteria, array $orderBy = null)
 * @method SellerPayout[]    findAll()
 * @method SellerPayout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellerPayoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, SellerPayout::class);
        $this->container = $container;
    }


    public function getPayouts($params) 
    {
        $queryBuilder = $this->createQueryBuilder('payout');
        $queryBuilder->orderBy('payout.'.$params['sort'], $params['order_by']);

        if(isset($params['company']) && $params['company']) {
            $queryBuilder->where('payout.company = :company')->setParameter('company', $params['company']->getId());
        }

        if(isset($params['seller']) && $params['seller']) {
            $queryBuilder->andWhere('payout.seller = :seller')->setParameter('seller', $params['seller']);
        }

        if(isset($params['seller_id']) && $params['seller_id']) {
            $queryBuilder->andWhere('payout.seller = :seller_id')->setParameter('seller_id', $params['seller_id']);
        }

        if(isset($params['status']) && $params['status'] && is_array($params['status'])) {
            $queryBuilder->andWhere('payout.status IN (:status)')->setParameter('status', $params['status']);
        } elseif(isset($params['status']) && $params['status']) {
            $queryBuilder->andWhere('payout.status = :status')->setParameter('status', $params['status']);
        }

        if(isset($params['payout_type']) && $params['payout_type']) {
            $queryBuilder->andWhere('payout.payoutType = :payout_type')->setParameter('payout_type', $params['payout_type']);
        }

        if(isset($params['payout_id']) && $params['payout_id']) {
            $queryBuilder->andWhere('payout.id = :payout_id')->setParameter('payout_id', $params['payout_id']);
        }

        if ((isset($params['start_date']) && !empty($params['start_date'])) && (isset($params['end_date']) && !empty($params['end_date']))) {
            $queryBuilder
            ->andWhere('payout.createdAt >= :createdon')
            ->setParameter('createdon', strtotime($params['start_date']))
            ->andWhere('payout.createdAt <= :expiredon')
            ->setParameter('expiredon', strtotime($params['end_date']));
        }

        $queryBuilder
                ->leftjoin(
                    'Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders',
                    'ord',
                    \Doctrine\ORM\Query\Expr\Join::WITH,
                    'ord.id = payout.orderId'
                );
                //->andWhere('ord.sellerStatus != 0');
                //->andWhere('CASE WHEN payout.payoutType != :PO THEN ord.sellerStatus != 0 END')
                //->setParameter('PO', 'O');

        $query = $queryBuilder->getQuery();

        return $this->createPagination($query, $params);
    }

    public function getSumOfPayout($sumOf, $params)
    {
        $queryBuilder = $this->createQueryBuilder('payout');
        $queryBuilder->select('sum(payout.'.$sumOf.')');
        $queryBuilder->where('payout.company = :company')->setParameter('company', $params['company']->getId());
        if (is_array($params['status'])) {
            $queryBuilder->andWhere('payout.status IN(:status)')->setParameter('status', $params['status']);
        } else {
            $queryBuilder->andWhere('payout.status = :status')->setParameter('status', $params['status']);
        }
        
        $queryBuilder->andWhere('payout.payoutType = :payout_type')->setParameter('payout_type', $params['payout_type']);
        
        if(isset($params['seller']) && !empty($params['seller'])) {
            $queryBuilder->andWhere('payout.seller = :seller')->setParameter('seller', $params['seller']);
        }
        return $queryBuilder->getQuery()
        ->getOneOrNullResult();
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
        if (isset($params['seller'])) {
            $queryBuilder->andwhere('sp.seller = :seller')->setParameter('seller', $params['seller']);
        }
        $query = $queryBuilder->getQuery();
        return $query->execute();
        
    }

    public function getSellersAccountingIds($params)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        
        $queryBuilder->select('p.id,s.id as seller_id');
        $queryBuilder->join(
                'Webkul\Modules\Wix\WixmpBundle\Entity\Seller',
                's',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'p.seller = s.id'
            );
        
        $queryBuilder->where('p.payoutType IN(:payoutType)')->setParameter('payoutType', ['O','P','W']);
        $queryBuilder->andwhere('p.status = :status')->setParameter('status', 'A');
        
        // if (isset($params['company']) && !empty($params['company'])) {
            // company is mandatory
            $queryBuilder->andWhere('p.company = :company')->setParameter('company', $params['company']);
        // } 

        if (isset($params['seller']) && !empty($params['seller'])) {
            $queryBuilder->andWhere('s.id = :seller')->setParameter('seller', $params['seller']);
        }

        if (isset($params['email']) && !empty($params['email'])) {
            $queryBuilder->andWhere('s.email = :email')->setParameter('email', $params['email']);
        }

        if (isset($params['valid']) && !empty($params['valid'])) {
            if ($params['valid'] == 'Y') {
                $queryBuilder->having('(SUM(p.orderAmount)- SUM(p.payoutAmount) - SUM(p.commissionAmount) > 0)');
            } elseif ($params['valid'] == 'N') {
                $queryBuilder->having('(SUM(p.orderAmount)- SUM(p.payoutAmount) - SUM(p.commissionAmount) <= 0)');
            }
            
        }
        if (isset($params['get_single_result'])) {
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } elseif (isset($params['get_all_results'])) {
            return $queryBuilder->getQuery()->getResult();
        } else {
            $query = $queryBuilder->getQuery();
            return $this->createPagination($query, $params);
        }
        
    }

    public function bulkUpdate($company, $data, $ids)
    {
        $query = $this->createQueryBuilder('p')
            ->update()
            ->where('p.company = :company')
            ->setParameter('company', $company)
            ->andWhere('p.id IN(:ids) ')
            ->setParameter('ids', $ids);
            // set value
        if (isset($data['payout_type'])) {
            $query->set('p.payoutType', ':payoutType')
                ->setParameter('payoutType', $data['payout_type']);
        }
        if (isset($data['comment'])) {
            $query->set('p.comment', ':comment')
                ->setParameter('comment', $data['comment']);
        }        
        if (isset($data['status'])) {
            $query->set('p.status', ':status')
                ->setParameter('status', $data['status']);
        }
        return $query->getQuery()->execute();
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
