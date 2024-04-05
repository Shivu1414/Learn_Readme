<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerUser;
use Doctrine\DBAL\DBALException;

class UserHelper extends WixMpBaseHelper
{
    public function update_user($user, $params)
    {
        if (isset($params['username'])  && !empty($params['username'])) {
            $user->setUsername($params['username']);
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $user->setEmail($params['email']);
        }
        if (isset($params['seller'])) {
            $user->setSeller($params['seller']);
        }
        if (isset($params['company'])) {
            $user->setCompany($params['company']);
        }
        if (isset($params['isRoot'])) {
            $user->setIsRoot($params['isRoot']);
        }
        if (isset($params['password'])) {
            $user->setPassword($params['password']);
        }
        if (isset($params['salt'])) {
            $user->setSalt($params['salt']);
        }
        if (isset($params['status'])) {
            $user->setStatus($params['status']);
        }
        if (isset($params['firstName'])) {
            $user->setFirstName($params['firstName']);
        }
        if (isset($params['lastName'])) {
            $user->setLastName($params['lastName']);
        }
        if (isset($params['phone'])) {
            $user->setPhone($params['phone']);
        }

        $user->SetUpdatedAt(time());
        $em = $this->entityManager;
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function get_user($params)
    {
        $user_repo = $this->entityManager->getRepository(SellerUser::class);
        $user_list = $user_repo->findOneBy($params);

        return $user_list;
    }

    public function get_all_users($params)
    {
        $default_params = [
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $user_repo = $this->entityManager->getRepository(SellerUser::class);
        $user_list = $user_repo->findByParams($params);

        return $user_list;
    }

    public function get_users($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $user_repo = $this->entityManager->getRepository(SellerUser::class);
        $user_list = $user_repo->getUsers($params);

        return array($user_list, $params);
    }
}