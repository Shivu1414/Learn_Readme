<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ManagerRegistry;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
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

    public function getProducts($filter, $company, $seller = null)
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
        if (!empty($filter['stockMin']) && !empty($filter['stockMax'])) {
            $query->andWhere(
                $query->expr()->between('p.stockLevel', $filter['stockMin'], $filter['stockMax'])
            );
        }
        if (isset($filter['status']) && !empty($filter['status'])) {
            $query->andWhere('p.status = :status')->setParameter('status', $filter['status']);
        }
        if (isset($filter['statuses']) && !empty($filter['statuses'])) {
            $query->andWhere('p.status IN(:statuses)')->setParameter('statuses', $filter['statuses']);
        }

        if ((isset($filter['start_date']) && !empty($filter['start_date'])) && (isset($filter['end_date']) && !empty($filter['end_date']))) {
            $query
            ->andWhere('p.timestamp >= :createdon')
            ->setParameter('createdon', strtotime($filter['start_date']))
            ->andWhere('p.timestamp <= :expiredon')
            ->setParameter('expiredon', strtotime($filter['end_date']));
        }

        if ($seller != null) {
            $query->andWhere('p.seller = :seller')
                ->setParameter('seller', $seller);
        }
        $query->andWhere('p.is_deleted = :is_deleted')->setParameter('is_deleted', 0);
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

    public function getProductCount($company, $seller = null)
    {
        $query = $this->createQueryBuilder('product');
        $query->select('count(product.id)');
        $query->andWhere('product.company = :company')->setParameter('company', $company);
        if ($seller != null) {
            $query->andWhere('product.seller = :seller')->setParameter('seller', $seller);
        }

        return $query->getQuery()->getOneOrNullResult();
    }

    public function findByParams($params = [], $id = null)
    {
        $query = $this->createQueryBuilder('product');

        if (isset($params['count'])) {
            $query->select('count(product.id)');
        } elseif (isset($params['sum'])) {
            $query->select('sum(product.gross_total)');
        }

        if (isset($params['status'])) {
            $query->andWhere('product.status IN (:status)')->setParameter('status', $params['status']);
        }

        if ($id) {
            $query->andWhere('product.id = :id')->setParameter('id', $id);
        }

        if (isset($params['company'])) {
            $query->andWhere('product.company = :company')->setParameter('company', $params['company']);
        }

        if (isset($params['seller'])) {
            $query->andWhere('product.seller = :seller')->setParameter('seller', $params['seller']);
        }

        if (isset($params['orderBy']['key']) && $params['orderBy']['value']) {
            $query->orderBy('product.'.$params['orderBy'], $params['orderBy']['value']);
        } else {
            $query->orderBy('product.id', 'ASC');
        }

        if (isset($params['max_result'])) {
            $query->setMaxResults($params['max_result']);
        } else {
            $query->setMaxResults(10);
        }

        if (isset($params['get_single_result'])) {
            return $query->getQuery()->getOneOrNullResult();
        } else {
            return $query->getQuery()->getResult();
        }
    }

    public function findProductCountByMonth($company, $seller = null)
    {
        $year = (int) date('Y');
        $data = [];

        for ($month = 1; $month <= 12; ++$month) {
            $startDate = new \DateTimeImmutable("$year-$month-01T00:00:00");
            $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

            $qb = $this->createQueryBuilder('object');
            $qb->select('COUNT(object.id) as count');
            $qb->where('object.timestamp BETWEEN :start AND :end');
            $qb->andWhere('object.company = :company');

            if ($seller) {
                $qb->andWhere('object.seller = :seller');
                $qb->setParameter('seller', $seller->getId());
            }

            $qb->setParameter('start', $startDate->getTimestamp());
            $qb->setParameter('end', $endDate->getTimestamp());
            $qb->setParameter('company', $company->getId());

            $return_data = $qb->getQuery()->getOneOrNullResult();
            $data[$month] = $return_data['count'] == null ? 0 : $return_data['count'];
        }

        return $data;
    }

    public function getProductDetail($company, $productId)
    {
        $query = $this->createQueryBuilder('product');
        $query->andWhere('product.company = :company')->setParameter('company', $company->getId());
        $query->andWhere('product._prod_id = :_prod_id')->setParameter('_prod_id', $productId);
        $result = $query->getQuery()->getResult();
        if (isset($result[0])) {
            return $result[0];
        }
    }

    public function isProductExists($company, $productId)
    {
        $query = $this->createQueryBuilder('product');
        $query->select('product.id');
        $query->andWhere('product.company = :company')->setParameter('company', $company->getId());
        $query->andWhere('product._prod_id = :_prod_id')->setParameter('_prod_id', $productId);

        return $query->getQuery()->getOneOrNullResult();
    }

    public function bulkUpdate($company, $data, $productIds)
    {
        $query = $this->createQueryBuilder('p')
            ->update()
            ->where('p.company = :company')
            ->setParameter('company', $company)
            ->andWhere('p.id IN(:ids) ')
            ->setParameter('ids', $productIds);
        // set value
        if (isset($data['name'])) {
            $query->set('p.name', ':name')
                ->setParameter('name', $data['name']);
        }
        if (isset($data['inventory_level'])) {
            $query->set('p.stock_level', ':stock_level')
                ->setParameter('stock_level', $data['inventory_level']);
        }
        if (isset($data['price'])) {
            $query->set('p.price', ':price')
                ->setParameter('price', $data['price']);
        }
        if (isset($data['status_to'])) {
            $query->set('p.status', ':status')
                ->setParameter('status', $data['status_to']);
        }
        if (isset($data['seller'])) {
            $query->set('p.seller', ':seller')
                ->setParameter('seller', $data['seller']);
            $query->andWhere('p.seller is NULL'); // do not override seller already assigned
        }

        return $query->getQuery()->execute();
    }
}
