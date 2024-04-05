<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ManagerRegistry;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Seller|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seller|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seller[]    findAll()
 * @method Seller[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, Seller::class);
        $this->container = $container;
    }

    public function getSeller($params)
    {
        $queryBuilder = $this->createQueryBuilder('seller');
        $queryBuilder->orderBy('seller.'.$params['sort'], $params['order_by']);

        if (isset($params['company']) && !empty($params['company'])) {
            $queryBuilder->where('seller.company = :company')->setParameter('company', $params['company']->getId());
        }

        if (isset($params['phone']) && !empty($params['phone'])) {
            $queryBuilder->andwhere('seller.phone = :phone')->setParameter('phone', $params['phone']);
        }

        if (isset($params['seller']) && !empty($params['seller'])) {
            $queryBuilder->andWhere('seller.id = :seller')->setParameter('seller', $params['seller']);
        }

        if (isset($params['name']) && !empty($params['name'])) {
            $queryBuilder->andWhere('seller.seller = :name')->setParameter('name', $params['name']);
        }

        if (isset($params['email']) && !empty($params['email'])) {
            $queryBuilder->andWhere('seller.email = :email')->setParameter('email', $params['email']);
        }

        if (isset($params['status']) && !empty($params['status']) && is_array($params['status'])) {
            $queryBuilder->andWhere('seller.status IN (:status)')->setParameter('status', $params['status']);
        } elseif (isset($params['status']) && !empty($params['status'])) {
            $queryBuilder->andWhere('seller.status = :status')->setParameter('status', $params['status']);
        }
        if (isset($params['only_inactive_sellers']) && $params['only_inactive_sellers']) {
            $queryBuilder->andWhere('seller.expireAt < :curTime')->setParameter('curTime', time());
        }
        if (isset($params['is_archieved'])) {
            $queryBuilder->andWhere('seller.isArchieved = :isArchieved')->setParameter('isArchieved', $params['is_archieved']);
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

    private function createPagination(Query $query, $params)
    {
        $paginator = $this->container->get('knp_paginator');

        return $paginator->paginate($query, $params['page'], $params['items_per_page'],array('wrap-queries'=>true));
    }

    public function getAllSellers($params)
    {
        $queryBuilder = $this->createQueryBuilder('seller');
        $queryBuilder->orderBy('seller.'.$params['sort'], $params['order_by']);

        if (isset($params['company']) && !empty($params['company'])) {
            $queryBuilder->where('seller.company = :company')->setParameter('company', $params['company']);
        }

        if (isset($params['status']) && !empty($params['status']) && is_array($params['status'])) {
            $queryBuilder->andWhere('seller.status IN (:status)')->setParameter('status', $params['status']);
        } elseif (isset($params['status']) && !empty($params['status'])) {
            $queryBuilder->andWhere('seller.status = :status')->setParameter('status', $params['status']);
        }

        if (isset($params['seller']) && !empty($params['seller'])) {
            $queryBuilder->andWhere('seller.seller = :seller')->setParameter('seller', $params['seller']);
        }

        if (isset($params['isArchived'])) {
            $queryBuilder->andWhere('seller.isArchieved = :isArchived')->setParameter('isArchived', $params['isArchived']);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getSellersAsOptions($params)
    {
        $queryBuilder = $this->createQueryBuilder('seller')
            ->addSelect('seller.id as id')
            ->addSelect('seller.seller as text');
        $queryBuilder->orderBy('seller.'.$params['sort'], $params['order_by']);

        if (isset($params['company']) && !empty($params['company'])) {
            $queryBuilder->where('seller.company = :company')->setParameter('company', $params['company']->getId());
        }
        if (isset($params['name']) && !empty($params['name'])) {
            $queryBuilder
                ->andWhere('seller.seller LIKE :name')
                ->setParameter('name', '%'.$params['name'].'%');
        }
        $query = $queryBuilder->getQuery();

        return $this->createPagination($query, $params);
    }

    /**
     * rempve records.
     */
    public function bulkRemove($params)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->delete();
        if (isset($params['ids'])) {
            $queryBuilder->andwhere('s.id IN (:ids)')->setParameter('ids', $params['ids']);
        }
        if (isset($params['company'])) {
            $queryBuilder->andwhere('s.company = :company')->setParameter('company', $params['company']);
        }
        $query = $queryBuilder->getQuery();

        return $query->execute();
    }
    public function getSellersCommissionList ($params) 
    {
        $queryBuilder = $this->createQueryBuilder('s');
        
        $queryBuilder->select('s as seller,SUM(p.orderAmount) as orderAmount, SUM(p.payoutAmount) as payoutAmount, SUM(p.commissionAmount) as commissionAmount');
        
        // $em = $this->getEntityManager();
        // $subQuery = $em->createQueryBuilder();
        // $subQuery->select('ptm.amount as amount,ptm.createdAt as lastPaidOn');
        // $subQuery->from('Webkul\Modules\Bigcommerce\MarketplaceBundle\Entity\SellerPayoutTransactionsMapping', 'ptm');
        // $subQuery->join(
        //     'Webkul\Modules\Bigcommerce\MarketplaceBundle\Entity\Seller',
        //     'ss',
        //     \Doctrine\ORM\Query\Expr\Join::WITH,
        //     'ptm.seller = ss.id'
        // );  
        //$subQuery->where('ptm.seller = ss.id');   
        // $subQuery->addOrderBy('ptm.id','desc');
        // $subQuery->setMaxResults(1);
       
        $queryBuilder->join(
                'Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout',
                'p',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'p.seller = s.id'
            );
        $queryBuilder->where('p.seller = s.id');
        $queryBuilder->groupBy('p.seller');
        
        $queryBuilder->andwhere('p.status IN (:status)')->setParameter('status', ['A']);
        
        if (isset($params['company']) && !empty($params['company'])) {
            $queryBuilder->andWhere('s.company = :company')->setParameter('company', $params['company']);
        }
        
        if (isset($params['seller']) && !empty($params['seller'])) {
            $queryBuilder->andWhere('s.id = :seller')->setParameter('seller', $params['seller']);
            // $subQuery->andwhere('ptm.seller = :seller')
            // ->setParameter('seller', $params['seller']);
        }

        if (isset($params['email']) && !empty($params['email'])) {
            $queryBuilder->andWhere('s.email = :email')->setParameter('email', $params['email']);
            // $subQuery->andWhere('ss.email = :email')->setParameter('email', $params['email']);
        }

        if (isset($params['valid']) && !empty($params['valid'])) {
            if ($params['valid'] == 'Y') {
                $queryBuilder->having('(SUM(p.orderAmount)- SUM(p.payoutAmount) - SUM(p.commissionAmount) > 0)');
            } elseif ($params['valid'] == 'N') {
                $queryBuilder->having('(SUM(p.orderAmount)- SUM(p.payoutAmount) - SUM(p.commissionAmount) <= 0)');
            }
            
        }
        // $queryBuilder->addSelect("(" . $subQuery->getDQL() .") AS addresstypeName"
        // );
        
        if (isset($params['get_single_result'])) {
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } elseif (isset($params['get_all_results'])) {
            return $queryBuilder->getQuery()->getResult();
        } else {
            $query = $queryBuilder->getQuery();
            return $this->createPagination($query, $params);
        }
    }

//    /**
//     * @return Seller[] Returns an array of Seller objects
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
    public function findOneBySomeField($value): ?Seller
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
