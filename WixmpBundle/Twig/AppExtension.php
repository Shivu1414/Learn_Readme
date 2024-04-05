<?php

namespace Webkul\Modules\Wix\WixmpBundle\Twig;

use Twig\Extension\AbstractExtension;

class AppExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            new \Twig_Function('get_wix_mp_seller_menu_list', array(AppRuntime::class, 'get_wix_mp_seller_menu_list')),
            new \Twig_Function('getWixCompanySellerList', array(AppRuntime::class, 'get_wix_company_seller_list')),
            new \Twig_Function('getWixCategoryTree', array(AppRuntime::class, 'get_wix_category_tree')),
            new \Twig_Function('getWixCompanyArchivedSellerList', array(AppRuntime::class, 'get_wix_company_archived_seller_list')),
            new \Twig_Function('jsonDecode', array(AppRuntime::class, 'json_decode')),
            new \Twig_Function('unserialize', array(AppRuntime::class, 'unserialize')),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('wixmp_status_info', array(AppRuntime::class, 'wix_order_status_name')),
            new \Twig_SimpleFilter('wixmp_fullfillment_status_info', array(AppRuntime::class, 'wixmp_fullfillment_status_info')),
        );
    }
}