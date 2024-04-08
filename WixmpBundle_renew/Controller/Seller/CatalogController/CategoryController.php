<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\CatalogController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webkul\Modules\Wix\WixmpBundle\Utils\CatalogHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
/**
 * @Route("/catalog", name="wixmp_seller_catalog_category_")
 * @Security("is_granted('ROLE_WIXMP_SELLER')")
 */
class CategoryController extends BaseController
{
    public function __construct(TranslatorInterface $translator, AdapterInterface $cache)
    {
        $this->translate = $translator;
        $this->cache = $cache;
    }

    /**
     * @Route("/category/", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function category(CompanyApplication $companyApplication, Request $request, CatalogHelper $catalog): Response
    {
        $commonHelper = $catalog->getHelper('common');
        $params['settingName'] = 'seller_allowed_categories';
        $params['company'] = $companyApplication->getCompany();
        $params['application'] = $companyApplication->getApplication();
        $setting_data = $commonHelper->get_section_setting($params);
        $seller = $this->getUser()->getSeller(); 
        $sellerplan = $seller->getCurrentPlan()->getConditions();
        $planApplicationData = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
        $allowedCategories = [];
        if (!empty($setting_data)) {
            $allowedCategories = !empty($setting_data->getValue())?explode(',', $setting_data->getValue()):[]; 
        }
        // seller specific allowed categories 
        if (!empty($seller->getAllowedCategories())) {
            $allowedCategories = $seller->getAllowedCategories();
        }
        if(!empty($allowedCategories)) {
            $categoryTree = $catalog->getCategoryTree($companyApplication, false, $allowedCategories);
        } else {
            $categoryTree = [];
        }
    
        
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'catalog/category_tree_simple',
            'title' => 'categories_manage_page',
            'list_count' => count($categoryTree),
            'categoryTree' => $categoryTree,
            'planApplicationData' => $planApplicationData,
            'sellerplan' => $sellerplan,
        ]);
    }
}
