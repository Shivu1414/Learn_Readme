<?php

namespace Webkul\Modules\Wix\WixmpBundle\Twig;

use Symfony\Component\Yaml\Parser;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;

class AppRuntime extends WixMpBaseHelper
{
    public function get_wix_mp_seller_menu_list($area = 'seller', $app_code = 'wixmp')
    {
        $yaml = new Parser();
        $data = null;

        $appHelper = $this->getHelper('application');
        $application = $appHelper->get_application(['code' => $app_code]);
        $app_path = $application->getAppPath(); 
        
        if (file_exists($this->container->getParameter('kernel.project_dir').'/'.$app_path.'/Resources/config/wk_seller_menu.yaml')) {
            $data = $yaml->parse(file_get_contents($this->container->getParameter('kernel.project_dir').'/'.$app_path.'/Resources/config/wk_seller_menu.yaml'));
        }
        
        return $data;
    }

    public function get_wix_company_seller_list($storeHash = '')
    {
        if (empty($storeHash)) {
            $storeHash = $this->request->get('storeHash');
        }
        $companyHelper = $this->getHelper('company');
        $company = $companyHelper->get_company(['storeHash' => $storeHash]);
        $sellerHelper = $this->getAppHelper('seller', 'wixmp');
        list($sellers, $params) = $sellerHelper->get_sellers(['company' => $company, "is_archieved" => 0]);
        
        return $sellers;
    }

    public function get_wix_company_archived_seller_list($storeHash = '')
    {
        if (empty($storeHash)) {
            $storeHash = $this->request->get('storeHash');
        }
        $companyHelper = $this->getHelper('company');
        $company = $companyHelper->get_company(['storeHash' => $storeHash]);
        $sellerHelper = $this->getAppHelper('seller', 'wixmp');
        list($sellers, $params) = $sellerHelper->get_sellers(['company' => $company, "is_archieved" => 1]);
        
        return $sellers;
    }

    public function wix_order_status_name($status_id)
    {   
        $salesHelper = $this->getAppHelper('sales');
        $status_list = $salesHelper->get_order_status_list();

        return isset($status_list[$status_id]) ? $status_list[$status_id] : "";
    }

    public function wixmp_fullfillment_status_info($status_id)
    {   
        $salesHelper = $this->getAppHelper('sales');
        $status_list = $salesHelper->get_order_fullfillment_status_list();

        return isset($status_list[$status_id]) ? $status_list[$status_id] : "";
    }

    public function get_wix_category_tree($onlyParent = false)
    {
        $catalogHelper = $this->getAppHelper('catalog', 'wixmp');
        $companyApplication = $this->container->get('app.runtime')->get_company_application();
        $categoryList = $catalogHelper->getCategoryTree($companyApplication, $onlyParent);
        if (!empty($categoryList)) {
            return $categoryList;
        }

        return [];
    }
    
    public function json_decode($json){
        return json_decode($json, true);
    }
    
    public function unserialize($data){
        return unserialize($data);
    }

}