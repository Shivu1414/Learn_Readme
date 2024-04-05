<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Webkul\Modules\Wix\WixmpBundle\Entity\CustomFeilds;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method CustomFeilds|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomFeilds|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomFeilds[]    findAll()
 * @method CustomFeilds[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomFeildsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct($registry, CustomFeilds::class);
        $this->container = $container;
    }

    private function createPagination(Query $query, $params)
    {
        $paginator = $this->container->get('knp_paginator');
        return $paginator->paginate($query, $params['page'],$params['items_per_page']);
    }

    public function getWixCustomField($params) {
        
        $queryBuilder = $this->createQueryBuilder('customFeild');
        $queryBuilder->orderBy('customFeild.'.$params['sort'], $params['order_by']);

        if(isset($params['company_application']) && $params['company_application']) {
            $queryBuilder->where('customFeild.company_application = :company_application')->setParameter('company_application', $params['company_application']);
        }

        if(isset($params['field_name']) && $params['field_name']) {
            $queryBuilder->andWhere('customFeild.feild_name = :feild_name')->setParameter('feild_name', $params['field_name']);
        }

        if(isset($params['id']) && $params['id']) {
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('customFeild.id', $params['id']));
        }

        // if(isset($params['status']) && !empty($params['status']) && is_array($params['status'])) {
        //     $queryBuilder->andWhere('sellerDoc.status IN (:status)')->setParameter('status', $params['status']);
        // } elseif(isset($params['status']) && !empty($params['status'])) {
        //     $queryBuilder->andWhere('sellerDoc.status = :status')->setParameter('status', $params['status']);
        // }

        $query = $queryBuilder->getQuery();

        return $this->createPagination($query, $params);
    }

    public function checkWixCustomFieldName($params)
    {
        $queryBuilder = $this->createQueryBuilder('customField');
        $queryBuilder->orderBy('customField.'.$params['sort'], $params['order_by']);

        if(isset($params['company_application']) && $params['company_application']) {
            $queryBuilder->where('customField.company_application = :company_application')->setParameter('company_application', $params['company_application']);
        }

        if(isset($params['field_name']) && $params['field_name']) {
            $queryBuilder->andWhere('customField.feild_name = :feild_name')->setParameter('feild_name', $params['field_name']);
        }

        if(isset($params['id']) && $params['id']) {
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('customField.id', $params['id']));
        }

        $query = $queryBuilder->getQuery()->getResult();

        return $query;
    }

    // /**
    //  * @return CustomFeilds[] Returns an array of CustomFeilds objects
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
    public function findOneBySomeField($value): ?CustomFeilds
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
