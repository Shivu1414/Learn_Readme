<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ManagerRegistry;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method SellerUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method SellerUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method SellerUser[]    findAll()
 * @method SellerUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellerUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, SellerUser::class);
        $this->container = $container;
    }

    public function findByParams($params = [], $id = null)
    {
        $query = $this->createQueryBuilder('su');

        if (isset($params['count'])) {
            $query->select('count(su.id)');
        } elseif (isset($params['sum'])) {
            $query->select('sum(su.gross_total)');
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $query->andWhere('su.status IN (:status)')->setParameter('status', $params['status']);
        }

        if ($id) {
            $query->andWhere('su.id = :id')->setParameter('id', $id);
        }

        if (isset($params['company']) && !empty($params['company'])) {
            $query->andWhere('su.company = :company')->setParameter('company', $params['company']);
        }

        if (isset($params['seller']) && !empty($params['seller'])) {
            $query->andWhere('su.seller = :seller')->setParameter('seller', $params['seller']);
        }

        if (isset($params['orderBy']['key']) && $params['orderBy']['value']) {
            $query->orderBy('su.'.$params['orderBy'], $params['orderBy']['value']);
        } else {
            $query->orderBy('su.id', 'ASC');
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

    public function getUsers($params)
    {
        $queryBuilder = $this->createQueryBuilder('user');
        $queryBuilder->orderBy('user.'.$params['sort'], $params['order_by']);

        if (isset($params['company']) && !empty($params['company'])) {
            $queryBuilder->where('user.company = :company')->setParameter('company', $params['company']->getId());
        }

        if (isset($params['id']) && !empty($params['id'])) {
            $queryBuilder->andWhere('user.id = :id')->setParameter('id', $params['id']);
        }

        if (isset($params['seller']) && !empty($params['seller'])) {
            $queryBuilder->andWhere('user.seller = :seller')->setParameter('seller', $params['seller']->getId());
        }

        if (isset($params['status']) && $params['status'] && is_array($params['status'])) {
            $queryBuilder->andWhere('user.status IN (:status)')->setParameter('status', $params['status']);
        } elseif (isset($params['status']) && $params['status']) {
            $queryBuilder->andWhere('user.status = :status')->setParameter('status', $params['status']);
        }

        if (isset($params['username']) && $params['username']) {
            $queryBuilder->andWhere('user.username = :username')->setParameter('username', $params['username']);
        }

        if (isset($params['name']) && $params['name']) {
            $queryBuilder->andWhere("CONCAT(user.firstName,' ',user.lastName) LIKE :name")->setParameter('name', '%'.$params['name'].'%');
        }

        if (isset($params['email']) && $params['email']) {
            $queryBuilder->andWhere('user.email = :email')->setParameter('email', $params['email']);
        }

        $query = $queryBuilder->getQuery();

        return $this->createPagination($query, $params);
    }

    private function createPagination(Query $query, $params)
    {
        $paginator = $this->container->get('knp_paginator');

        return $paginator->paginate($query, $params['page'], $params['items_per_page']);
    }

    public function getUsersByUsernnameOrEmail($params): ?SellerUser
    {
        $query = $this->createQueryBuilder('user')
            ->andWhere('user.username = :username')
            ->orWhere('user.email = :username')->setParameter('username', $params['username'])
            ->andWhere('user.company = :company')->setParameter('company', $params['company'])
            ->getQuery();

        return $query->getResult();
    }

    public function removeUsers($userIds)
    {
        $queryBuilder = $this->createQueryBuilder('su');
        $queryBuilder->delete()->where('su.id IN (:user_ids)')->setParameter('user_ids', $userIds);
        $query = $queryBuilder->getQuery();
        try {
            return $query->execute();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function bulkUpdate($company, $data, $userIds)
    {
        $query = $this->createQueryBuilder('su')
            ->update()
            ->where('su.company = :company')
            ->setParameter('company', $company)
            ->andWhere('su.id IN(:ids) ')
            ->setParameter('ids', $userIds);
        // set value

        if (isset($data['status'])) {
            $query->set('su.status', ':status')
                ->setParameter('status', $data['status']);
            // if ($data['status'] != 'A') {
            //     $query->andWhere('su.isRoot = :rootuser ')
            //         ->setParameter('rootuser', 'N'); //do not disable or block ROOT USERS
            // }
        }

        return $query->getQuery()->execute();
    }

    public function bulkDelete($company, $userIds = null, $deleteRoot = false)
    {
        $query = $this->createQueryBuilder('su')
            ->delete()
            ->where('su.company = :company')
            ->setParameter('company', $company);
        if (!empty($userIds)) {
            $query->andWhere('su.id IN(:ids) ')
            ->setParameter('ids', $userIds);
        }
        if (!$deleteRoot) {
            $query->andWhere('su.isRoot = :rootuser ')
            ->setParameter('rootuser', 'N'); //ONLY DELETE ROOT USERS
        }

        return $query->getQuery()->execute();
    }

    //    /**
    //     * @return SellerUser[] Returns an array of SellerUser objects
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
    public function findOneBySomeField($value): ?SellerUser
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
