<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\CatalogController;

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
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\Modules\Wix\WixmpBundle\Entity\Collections;
use Doctrine\ORM\EntityManagerInterface;
/**
 * @Route("/catalog", name="wixmp_catalog_category_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class CategoryController extends BaseController
{
    public function __construct(TranslatorInterface $translator, AdapterInterface $cache, EntityManagerInterface $entityManager)
    {
        $this->translate = $translator;
        $this->cache = $cache;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/category/", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function category(CompanyApplication $companyApplication, Request $request, CatalogHelper $catalog): Response
    {
        $categoryTree = $catalog->getCategoryTree($companyApplication);
        $company = $companyApplication->getCompany();
        $planApplicationData = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
        $mpCatData = [];
        
        if(isset($request->request->all()['category_commission']) && !empty($request->request->all()['category_commission'])) {
            $commissionUpdate = $catalog->updateMpCollections($request->request->all()['category_commission'], $company);
            if(isset($commissionUpdate)) {
                $this->addFlash(
                   'success',
                   $this->translate->trans('commission_update')
                );
            }else {
                $this->addFlash(
                   'danger',
                   'Max two digits allowed'
                );
            }
            $params['get_all_results'] = true; 
            $params['company'] = $company;
            $mpCategories = $catalog->getAllMpCollections($params);
            if(isset($mpCategories) && !empty($mpCategories)) {
                foreach($mpCategories as $mpCategory) {
                    $mpCatData[$mpCategory->getId()] = $mpCategory->getComission();
                }
            }
        }
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'breadcrums_template' => 'common/breadcrums',
            'template_name' => 'catalog/category_tree_simple',
            'title' => 'collections_manage_page',
            'list_count' => count($categoryTree),
            'categoryTree' => $categoryTree,
            'mpCatData' => $mpCatData,
            'planApplicationData' => $planApplicationData
        ]);
    }
    /**
     * @Route("/sync", name="sync")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function syncCategory(CompanyApplication $companyApplication, Request $request, CatalogHelper $catalog): Response
    {
        $company = $companyApplication->getCompany();

        $response = $catalog->sync_collections($request, $companyApplication);

        //$this->addFlash('success', $this->translate->trans('sync_successfully'));

        return new JsonResponse($response);
    }
     /**
     * @Route("/sync/cache", name="sync_cache")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function syncCategoryCache(CompanyApplication $companyApplication, Request $request, CatalogHelper $catalog): Response
    {
        $company = $companyApplication->getCompany();

        $response = $catalog->sync_collections_cache($request, $companyApplication);

        //$this->addFlash('success', $this->translate->trans('sync_successfully'));

        return new JsonResponse($response);
    }

    /**
     * @Route("/create/{name}", name="create")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function createCollection(CompanyApplication $companyApplication, Request $request, CatalogHelper $catalog, $name)
    {
        $response = $catalog->createCollections($name);
        return new Response("Created");
    }
}
