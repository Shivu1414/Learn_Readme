<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Repository;

use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\CategoryMapping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<CategoryMapping>
 *
 * @method CategoryMapping|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoryMapping|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoryMapping[]    findAll()
 * @method CategoryMapping[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, CategoryMapping::class);
        $this->container = $container;
    }

    public function getCategoryMappings($filter, $company)
    {
        $default_filter = [
            'page' => 1,
            'limit' => 10,
            'name' => '',
            'sort' => 'id',
            'order_by' => 'desc',
        ];

        $filter = array_merge($default_filter, $filter);
        $ordering = 'cm.'.$filter['sort'].' '.$filter['order_by'];
        if (isset($filter['fullordering']) && !empty($filter['fullordering'])) {
            $ordering = 'cm.'.$filter['fullordering'];
        }
        $query = $this->createQueryBuilder('cm')
            ->where('cm.company = :company')
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
            $query->andWhere('cm.id IN(:ids)')->setParameter('ids', $filter['ids']);
        }
        if (isset($filter['id']) && !empty($filter['id'])) {
            $query->andWhere('cm.id = :id')->setParameter('id', $filter['id']);
        }
        if (isset($filter['sku']) && !empty($filter['sku'])) {
            $query
                ->andWhere('cm.sku LIKE :sku')
                ->setParameter('sku', '%'.$filter['sku'].'%');
        }
        if (isset($filter['name']) && !empty($filter['name'])) {
            $query
                ->andWhere('cm.name LIKE :name')
                ->setParameter('name', '%'.$filter['name'].'%');
        }
        if (!empty($filter['min_price']) && !empty($filter['max_price'])) {
            $query->andWhere(
                $query->expr()->between('cm.price', (int) $filter['min_price'], (int) $filter['max_price'])
            );
        }
        // if (!empty($filter['stockMin']) && !empty($filter['stockMax'])) {
        //     $query->andWhere(
        //         $query->expr()->between('p.stockLevel', $filter['stockMin'], $filter['stockMax'])
        //     );
        // }
        if (isset($filter['status']) && !empty($filter['status'])) {
            $query->andWhere('cm.sync_status = :status')->setParameter('status', $filter['status']);
        }
        if (isset($filter['statuses']) && !empty($filter['statuses'])) {
            $query->andWhere('cm.sync_status IN(:statuses)')->setParameter('statuses', $filter['statuses']);
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
    public function add(CategoryMapping $entity, bool $flush = true): void
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
    public function remove(CategoryMapping $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return CategoryMapping[] Returns an array of CategoryMapping objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CategoryMapping
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
