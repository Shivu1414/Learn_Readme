<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Repository;

use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\Products;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @extends ServiceEntityRepository<Products>
 *
 * @method Products|null find($id, $lockMode = null, $lockVersion = null)
 * @method Products|null findOneBy(array $criteria, array $orderBy = null)
 * @method Products[]    findAll()
 * @method Products[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, Products::class);
        $this->container = $container;
    }

    public function getProducts($filter, $company)
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
        if (isset($filter['sku']) && !empty($filter['sku'])) {
            $query
                ->andWhere('p.sku LIKE :sku')
                ->setParameter('sku', '%'.$filter['sku'].'%');
        }
        if (isset($filter['name']) && !empty($filter['name'])) {
            $query
                ->andWhere('p.name LIKE :name')
                ->setParameter('name', '%'.$filter['name'].'%');
        }
        if (!empty($filter['min_price']) && !empty($filter['max_price'])) {
            $query->andWhere(
                $query->expr()->between('p.price', (int) $filter['min_price'], (int) $filter['max_price'])
            );
        }
        // if (!empty($filter['stockMin']) && !empty($filter['stockMax'])) {
        //     $query->andWhere(
        //         $query->expr()->between('p.stockLevel', $filter['stockMin'], $filter['stockMax'])
        //     );
        // }
        if (isset($filter['status']) && !empty($filter['status'])) {
            $query->andWhere('p.sync_status = :status')->setParameter('status', $filter['status']);
        }
        if (isset($filter['statuses']) && !empty($filter['statuses'])) {
            $query->andWhere('p.sync_status IN(:statuses)')->setParameter('statuses', $filter['statuses']);
        }

        // if ((isset($filter['start_date']) && !empty($filter['start_date'])) && (isset($filter['end_date']) && !empty($filter['end_date']))) {
        //     $query
        //     ->andWhere('p.timestamp >= :createdon')
        //     ->setParameter('createdon', strtotime($filter['start_date']))
        //     ->andWhere('p.timestamp <= :expiredon')
        //     ->setParameter('expiredon', strtotime($filter['end_date']));
        // }

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

    public function getProductCount($company)
    {
        $query = $this->createQueryBuilder('product');
        $query->select('count(product.id)');
        $query->andWhere('product.company = :company')->setParameter('company', $company);
        
        return $query->getQuery()->getOneOrNullResult();
    }

    public function isProductExists($company, $productId)
    {
        $query = $this->createQueryBuilder('product');
        $query->select('product.id');
        $query->andWhere('product.company = :company')->setParameter('company', $company->getId());
        $query->andWhere('product.wix_prod_id = :_prod_id')->setParameter('_prod_id', $productId);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Products $entity, bool $flush = true): void
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
    public function remove(Products $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Products[] Returns an array of Products objects
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
    public function findOneBySomeField($value): ?Products
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
