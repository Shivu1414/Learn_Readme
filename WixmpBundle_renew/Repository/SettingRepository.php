<?php

namespace Webkul\Modules\Wix\WixmpBundle\Repository;

use Webkul\Modules\Wix\WixmpBundle\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Setting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Setting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Setting[]    findAll()
 * @method Setting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }
    
    public function getSetting($filter)
    {
        $query = $this->createQueryBuilder('s');
        if (isset($filter['fields']) && !empty($filter['fields'])) {
            $fields = $filter['fields'];
            if (!is_array($filter['fields'])) {
                $fields = explode(',', $filter['fields']);
            }
            $fields =  array_map(function($value) { return 's.'.$value; }, $fields);
            $query->select($fields);
        }       
        if (isset($filter['companyApplication']) && !empty($filter['companyApplication'])) {
            $query->andWhere('s.companyApplication = :companyApplication')->setParameter('companyApplication', $filter['companyApplication']);
        }
        if (isset($filter['seller']) && !empty($filter['seller'])) {
            $query->andWhere('s.seller = :seller')->setParameter('seller', $filter['seller']);
        }
        if (isset($filter['area']) && !empty($filter['area'])) {
            $query->andWhere('s.area = :area')->setParameter('area', $filter['area']);
        }
        if (isset($filter['area']) && !empty($filter['area'])) {
            $query->andWhere('s.area = :area')->setParameter('area', $filter['area']);
        }
        return $query->getQuery()->getOneOrNullResult();

    }
    public function bulkRemove($params)
    {
        $query = $this->createQueryBuilder('s')
            ->delete();
        if (isset($params['companyApplication'])) {
            $query->where('s.companyApplication = :companyApplication')
                ->setParameter('companyApplication', $params['companyApplication']);
        }

        return $query->getQuery()->execute();
    }

    // /**
    //  * @return Setting[] Returns an array of Setting objects
    //  */
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
    public function findOneBySomeField($value): ?Setting
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
