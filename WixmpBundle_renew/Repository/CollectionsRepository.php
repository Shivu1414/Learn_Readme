<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ManagerRegistry;
use Webkul\Modules\Wix\WixmpBundle\Entity\Collections;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Products|null find($id, $lockMode = null, $lockVersion = null)
 * @method Products|null findOneBy(array $criteria, array $orderBy = null)
 * @method Products[]    findAll()
 * @method Products[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, Collections::class);
        $this->container = $container;
    }

    public function isCollectionExists($company, $collectionId)
    {
        $query = $this->createQueryBuilder('collection');
        $query->select('collection.id');
        $query->andWhere('collection.company = :company')->setParameter('company', $company->getId());
        $query->andWhere('collection._collectionId = :_collectionId')->setParameter('_collectionId', $collectionId);

        return $query->getQuery()->getOneOrNullResult();
    }

    public function getCollections($filter, $company)
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
        
        if (isset($filter['get_all_results'])) {
            return $query->getQuery()->getResult();
        } elseif (isset($filter['get_single_result'])) {
            return $query->getQuery()->getOneOrNullResult();
        } else {
            return $this->createPagination($query->getQuery(), $filter);
        }
    }

    private function createPagination(Query $query, $params)
    {
        $paginator = $this->container->get('knp_paginator');

        return $paginator->paginate($query, $params['page'], $params['items_per_page']);
    }

    public function removeAllCollections($company)
    {
        $query = $this->createQueryBuilder('c')
                ->delete()
                ->where('c.company = :company')->setParameter('company', $company->getId());
        
        return $query->getQuery()->execute();
    }
}
