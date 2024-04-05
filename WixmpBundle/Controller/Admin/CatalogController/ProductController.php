<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\CatalogController;

use App\Core\BaseController;
use App\Helper\CommonHelper;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Webkul\Modules\Wix\WixmpBundle\Utils\CatalogHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Webkul\Modules\Wix\WixmpBundle\Form\Catalog\ProductsType;
use Webkul\Modules\Wix\WixmpBundle\Utils\PlatformHelper;
use App\Helper\MediaHelper;
use Webkul\Modules\Wix\WixmpBundle\Events\CatalogEvent;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\IsNull;

/**
 * @Route("/catalog/product", name="wixmp_catalog_product_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class ProductController extends BaseController
{
    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher, AdapterInterface $cache, EntityManagerInterface $entityManager)
    {
        $this->event_dispatcher = $eventDispatcher;
        $this->translate = $translator;
        $this->cache = $cache;
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/add", name="add")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function add(Request $request, CompanyApplication $companyApplication, CatalogHelper $catalogHelper, PlatformHelper $platformHelper, MediaHelper $mediaHelper)
    {
        $company = $companyApplication->getCompany();

        $planApplicationData = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();

        $catalogEvent = new CatalogEvent($companyApplication);
        $this->event_dispatcher->dispatch(
            $catalogEvent,
            CatalogEvent::WIX_CATALOG_PRODUCT_ADD
        );

        if ($catalogEvent->getActionAllowed() == 'N') {
            if (!empty($request->query->get('return_uri'))) { // return to previous url
                return $this->redirect(base64_decode($request->query->get('return_uri')));
            }

            return $this->redirectToRoute('wixmp_catalog_product_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }

        $platform_product = [];
        if($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3' ) {
            $form = $catalogHelper->getProductFormVillum($platform_product, null); 
        } else {
            $form = $catalogHelper->getProductForm($platform_product, null);
        }
        $child = $form->all();
        
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            
            $data = $form->getData();  
            $all_params = $request->request->all()['form'];
       
            $catalogEvent = new CatalogEvent($companyApplication);
            $catalogEvent->setProductData($data);
            $catalogEvent->setProductParams($all_params);
            $this->event_dispatcher->dispatch(
                $catalogEvent,
                CatalogEvent::WIX_CATALOG_PRODUCT_ADD
            );
            $data = $catalogEvent->getProductData();
            $all_params = $catalogEvent->getProductParams(); 
            if ($data['trackInventory']){    
                if (!preg_match("/^(0|[1-9]\d{0,4}|99999)$/", $data['quantity'])) {
                    $this->addFlash("danger",$this->translate->trans('inventory_quantity_validation'));
                    $referer = $request->headers->get('referer');   
                    return new RedirectResponse($referer);
                }
            }
            if($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3') {
                $data['awards'] = $all_params['awards'];
                $data['awardsValue'] = $all_params['awardsValue'];
                $data['conditionAdd'] = isset($all_params['conditionAdd']) ? $all_params['conditionAdd'] : '';
                $data['conditionFourthAdd'] = isset($all_params['conditionFourthAdd']) ? $all_params['conditionFourthAdd'] : '';
                list($platform_product_data, $notifications) = $catalogHelper->arrangeUpdatePlatformProductVellum($data, $all_params); 
            } 
            if($company->getStoreHash() != 'RishabhStore-SAAS727a' && $company->getStoreHash() != 'VILLUMIb6f3' ) {
                list($platform_product_data, $notifications) = $catalogHelper->arrangeUpdatePlatformProduct($data, $all_params); 
            }

            $drag_images['images'] = $platform_product_data['images'];
           
            foreach($drag_images['images'] as $image) {
                $drag_url[] = $image['image_url']."/images.jpg";
            }
           
            // $drag_url = $platform_product_data['images'][0]['image_url']."/images.jpg";
            $extraParams = [];
            $extraParams['categories'] = isset($all_params['categories']) ? $all_params['categories'] : [];
            $extraParams['commission_type'] = isset($all_params['commission_type']) ? $all_params['commission_type'] : "fixed";
            $extraParams['commission'] = isset($all_params['commission']) ? ( $all_params['commission'] != "" ? $all_params['commission'] : 0.0  ) : 0.0;
           
            if(isset($company) && ($company->getStorehash() == 'RishabhStore-SAAS727a' || $company->getStorehash() == 'VILLUMIb6f3')) {
                list($isSuccess, $productResponse, $product) = $catalogHelper->create_product_vellum(
                    $companyApplication, $platform_product_data, $data, null, $extraParams
                );
            } else {
                list($isSuccess, $productResponse, $product) = $catalogHelper->create_product(
                    $companyApplication, $platform_product_data, $data, null, $extraParams
                );
            }
            if (isset($productResponse->message) && !empty($productResponse->message)) {
                $this->addFlash('danger', ucfirst($productResponse->message));
            }
            
            if ($isSuccess) {
                $productId = isset($productResponse->product->id) ? $productResponse->product->id : "";
                
                $pro_images = isset($data['images']) ? $data['images'] : [];

                if (!empty($pro_images)) {
                    foreach ($pro_images as $imageFile) {

                        $imageFileName = explode(".",$imageFile->getClientOriginalName());
                        $imageFileName = (isset($imageFileName[0]) && isset($imageFileName[1])) ? $imageFileName[0].'.'.$imageFileName[1] : "wixmp_product_".$product->getId();
                        $imageFileName = str_replace(' ','',$imageFileName);
                         
                        //validate Media file
                        $isValidMedia = $mediaHelper->validate($imageFile, ['type' => 'image']);
                        if ($isValidMedia !== false) {
                            //upload profile logo
                            $media = $mediaHelper->addMedia($imageFile, $imageFileName, '/wixmp/product/'.$product->getId().'/', null, $withExtension = false);
                            if (!empty($media)) {
                                //add mapping
                                $mediaHelper->addMapping($media, $product->getId(), 'wixmp_product');

                                $application = $companyApplication->getApplication();
                                $appPath = $application->getPlatform()->getCode().'/'.$application->getCode().'/'.$companyApplication->getId().'/';
                                $path = ltrim('/wixmp/product/'.$product->getId().'/', '/');
                                $path = $appPath.ltrim($path, $appPath);
                                
                                $mediaData['media'] = [
                                    [
                                        "url" => $request->getUriForPath('/media/'.$path.$imageFileName),
                                       //"url" => "https://cdn11.bigcommerce.com/s-ajo5dorpkd/products/410/images/513/New-fashion-cute-turtle-men-and-women-3D-printing-casual-short-sleeved-T-shirt__25734.1639398108.220.290.jpg?c=1"
                                    ]
                                ]; 
                                list($response, $error) = $platformHelper->add_product_media($productId, $mediaData);
                               
                            }
                        }
                    }
                }
                if(empty($mediaData) && isset($drag_url)) {
                    foreach($drag_url as $url){
                        $data['images'][] = [
                            'image_url' =>  $url,
                        ];
                    }
                    if (isset($data['images']) && !empty($data['images'])) {
                        foreach($data['images'] as $image) {
                            if (is_array($image)) {
                                $mediaData['media'] = [
                                    [
                                        "url" => isset($image['image_url']) ? $image['image_url'] : "",
                                        //"url" => "https://cdn11.bigcommerce.com/s-ajo5dorpkd/products/410/images/513/New-fashion-cute-turtle-men-and-women-3D-printing-casual-short-sleeved-T-shirt__25734.1639398108.220.290.jpg?c=1"
                                    ]
                                ]; 
                                list($response, $error) = $platformHelper->add_product_media($productId, $mediaData);
                            }
                        }
                    }
                }
                sleep(4);
                list($productResponse, $notifications) = $catalogHelper->get_product($productId);
                $productThumbnail = isset($productResponse->media->mainMedia->thumbnail->url) ? $productResponse->media->mainMedia->thumbnail->url : "";
                $product->setImage($productThumbnail);
                $this->entityManager->persist($product);
                $this->entityManager->flush();

                if($company->getStorehash() == 'RishabhStore-SAAS727a' || $company->getStorehash() == 'VILLUMIb6f3') {
                    foreach ($notifications as $type => $messages) {
                        foreach ($messages as $message) { 
                            $result['type'] = $type;
                            $result['message'] = $message;
                            if ($type == "success") {
                                $result['url'] = $this->generateUrl("wixmp_catalog_product_manage",[
                                    'storeHash' => $request->get('storeHash')]);
                                $this->addFlash($type, $message);
                            }
                        }
                    }
                    !isset($result) ? $result = ['type' => 'success', 'message' => $this->translate->trans('message.product.added_success'), 'url' => $this->generateUrl("wixmp_catalog_product_manage", ['storeHash' => $request->get('storeHash') ] )] : '';
                    return new JsonResponse($result);
                }

                $this->addFlash('success', $this->translate->trans('message.product.added_success'));

                return $this->redirectToRoute("wixmp_catalog_product_manage",[
                    'storeHash' => $request->get('storeHash')
                ]);
            }            
        }

        $categoryTree = $catalogHelper->getCategoryTree($companyApplication);
        if (empty($categoryTree)) {
            //$this->addFlash('warning', $this->translate->trans('message.product.add.please_choose_a_category'));
        }

        if(($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3') && $form->isSubmitted() && $formError = $this->getErrorMessages($form)) {
            foreach ($formError as $error) { 
                $result['type'] = 'danger';
                $result['message'] = $error;
            }
            return new JsonResponse($result);
        }
        $awardsItems = $catalogHelper->getAwards();
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'catalog/product_update',
            'title' => 'product_add_page',
            'form' => $form->createView(),
            'company' => $company,
            'product' => $platform_product,
            'selected_cats' => [],
            'categoryTree' => $categoryTree,
            'plan_application_data' => $planApplicationData,
            "awardsItems" => $awardsItems,
        ]);
    }
    /**
     * @Route("/", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function manage(Request $request, CompanyApplication $companyApplication, CatalogHelper $catalogHelper)
    {
        $company = $companyApplication->getCompany();
        $params['company'] = $company;
        $form = $this->createForm(ProductsType::class);
        $form->handleRequest($request);
       
        if ($form->isSubmitted() && $form->isValid()) { 
            $formData = $form->getData(); 
            $requestData = $request->request->all();
           
            if (isset($formData['batch_action'])) {
                $notifications = $catalogHelper->performBatchAction($request, $formData, $companyApplication);
                if (!empty($notifications)) {
                    foreach ($notifications as $notification) {
                        $this->addFlash($notification['type'], $notification['message']);
                    }
                }
            } elseif (isset($requestData['assign_seller'])) {
                
                if (!isset($requestData['product_ids']) || empty($requestData['product_ids'])) {
                    $notifications[] = ['type' => 'danger', 'message' => 'No product found to assign seller'];
                } elseif (!isset($requestData['seller']) || empty($requestData['seller'])) {
                    $notifications[] = ['type' => 'danger', 'message' => 'No seller found to assign'];
                } else {
                    // assisgn seller to products
                    $notifications = $catalogHelper->assignProductsToSeller($requestData['product_ids'], $requestData['seller'], $companyApplication);
                }

                if (!empty($notifications)) {
                    foreach ($notifications as $notification) {
                        $this->addFlash($notification['type'], $notification['message']);
                    }
                }
            }

            $referer = $request->headers->get('referer');

            return $this->redirect($referer);
        }

        $params = $request->query->all();
        if (empty($params['limit'])) {
            $params['limit'] = 10;
        }
        if (empty($params['sort'])) {
            $params['sort'] = 'id';
        }
        if (empty($params['order_by'])) {
            $params['order_by'] = 'desc';
        }
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        
        if (isset($params['seller']) && !empty($params['seller'])) {
            $params['seller'] = $params['seller'];
        } else {
            $params['seller'] = null;
        }

        list($products, $params) = $catalogHelper->get_products($params);
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'breadcrums_template' => 'common/breadcrums',
            'template_name' => 'catalog/product_manage',
            'title' => 'products',
            'products' => $products,
            'list_count' => $products->getTotalItemCount(),
            'filter' => $params,
            'search' => $request->query->all(),
            'companyApplication' => $companyApplication,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/update/{product_id}", name="update")
     * @ParamConverter("product", options={"mapping": {"product_id": "id"}})
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @Method("GET|POST")
     */
    public function update(CompanyApplication $companyApplication, Products $product, Request $request, CatalogHelper $catalogHelper, MediaHelper $mediaHelper)
    {
        $updated = false;
        $notifications = [];
        $company = $companyApplication->getCompany();

        $planApplicationData = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
        
        if ($request->get('cc')) {
            $this->cache->deleteitem('product_data_'.$product->getProdId());
        }
        $productCacheList = $this->cache->getItem('product_data_'.$product->getProdId());
        
        //if (!$productCacheList->isHit()) {
            $params = [];
            list($platform_product_raw, $notifications) = $catalogHelper->get_product($product->getProdId(), $params);
            
            foreach ($notifications as $type => $messages) {
                foreach ($messages as $message) {
                    $this->addFlash($type, $message);
                }
            }

            if (empty($platform_product_raw)) {
                if (!empty($request->query->get('return_uri'))) { // return to previous url
                    return $this->redirect(base64_decode($request->query->get('return_uri')));
                }
                
                return $this->redirectToRoute('wixmp_catalog_product_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
            }

            //  Get Description 
            if($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3') {
                $customDesc = explode("\n",$platform_product_raw->description);
                $customDescs = [];
                $i = 0;
                foreach ($customDesc as $key => $value) {
                    if (!empty($value)) {
                        $customDescs['condition_' . $i] = array('key' => $key, 'value' => $value);
                        $i++;
                    }
                }
                $product->setDescription(serialize($customDescs));
            }

            $productCacheList->set($platform_product_raw);
            $productCacheList->expiresAfter(60);  // data saved only for 300 seconds Increase if necessary
            $this->cache->save($productCacheList);
        //}

        $platform_product_raw = $productCacheList->get();

        $images = isset($platform_product_raw->media->items) ? $platform_product_raw->media->items : (object) array();

        $platform_product = json_decode(json_encode($platform_product_raw), true);

        $platform_product['price'] = (isset($platform_product['price']) && isset($platform_product['price']['price'])) ? $platform_product['price']['price'] : 0.0;

        //$images = isset($platform_product['media']['items']) ? $platform_product['media']['items'] : [];

        $platform_product['discount_type'] = isset($platform_product['discount']['type']) ? $platform_product['discount']['type'] : "";
        $platform_product['discount'] = isset($platform_product['discount']['value']) ? $platform_product['discount']['value'] : "";

        $platform_product['commission_type'] = $product->getCommissionType();
        $platform_product['commission'] = $product->getCommission();
        $platform_product['trackInventory'] = $platform_product["stock"]["trackInventory"];
        $platform_product['quantity'] = $platform_product["stock"]["trackInventory"] ? $platform_product["stock"]["quantity"] : null;
        $platform_product['inventory_status'] = $platform_product["stock"]["inStock"];
        $extra_data = $product->getExtraDetails();
        $dataArray = unserialize($extra_data);
        
    
        if($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3') {
            $form = $catalogHelper->getProductFormVillum($platform_product, null);
        } else {
            $form = $catalogHelper->getProductForm($platform_product, null);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() ) {
            
            $data = $form->getData(); 
            $all_params = $request->request->all()['form']; 
            $active_tab = $request->request->all()['active_tab'];
            $catalogEvent = new CatalogEvent($companyApplication);
            $catalogEvent->setProductData($data);
            $catalogEvent->setProductParams($all_params);
            $this->event_dispatcher->dispatch(
                $catalogEvent, CatalogEvent::CATALOG_PRODUCT_UPDATE
            );
            $data = $catalogEvent->getProductData();
            $all_params = $catalogEvent->getProductParams();
            if ($data['trackInventory']){    
                if (!preg_match("/^(0|[1-9]\d{0,4}|99999)$/", $data['quantity'])) {
                    $this->addFlash("danger",$this->translate->trans('inventory_quantity_validation'));
                    return $this->redirectToRoute('wixmp_catalog_product_update', ['storeHash' => $companyApplication->getCompany()->getStoreHash(), 'product_id' => $product->getId(), 'active_tab' => $active_tab, "cc" => 1]);
                }
            }
            if($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3') {
                $data['awards'] = $all_params['awards'];
                $data['awardsValue'] = $all_params['awardsValue'];
                $data['conditionAdd'] = isset($all_params['conditionAdd']) ? $all_params['conditionAdd'] : '';
                $data['conditionFourthAdd'] = isset($all_params['conditionFourthAdd']) ? $all_params['conditionFourthAdd'] : '';
                list($platform_product_data, $notifications) = $catalogHelper->arrangeUpdatePlatformProductVellum($data, $all_params);
            } else{
                list($platform_product_data, $notifications) = $catalogHelper->arrangeUpdatePlatformProduct($data, $all_params);
            }
          
            $drag_images['images'] = $platform_product_data['images'];
            foreach($drag_images['images'] as $image) {
                $drag_url[] = $image['image_url']."/images.jpg";
            }
            // $drag_url = $platform_product_data['images'][0]['image_url']."/images.jpg";
            $pro_images = isset($data['images']) ? $data['images'] : []; 
           
            if (!empty($pro_images)) {
                foreach ($pro_images as $imageFile) {
                    
                    $imageFileName = explode(".",$imageFile->getClientOriginalName());
                    $imageFileName = (isset($imageFileName[0]) && isset($imageFileName[1])) ? $imageFileName[0].'.'.$imageFileName[1] : "wixmp_product_".$product->getId();
                    $imageFileName = str_replace(' ','',$imageFileName); 
                    
                    //validate Media file
                    $isValidMedia = $mediaHelper->validate($imageFile, ['type' => 'image']);
                    if ($isValidMedia !== false) {
                        //upload profile logo
                        $media = $mediaHelper->addMedia($imageFile, $imageFileName, '/wixmp/product/'.$product->getId().'/', null, $withExtension = false);
                        if (!empty($media)) {
                            //add mapping
                            $mediaHelper->addMapping($media, $product->getId(), 'wixmp_product');
                            
                            $application = $companyApplication->getApplication();
                            $appPath = $application->getPlatform()->getCode().'/'.$application->getCode().'/'.$companyApplication->getId().'/';
                            $path = ltrim('/wixmp/product/'.$product->getId().'/', '/');
                            $path = $appPath.ltrim($path, $appPath); 
                            //unset($data['images']);
                            $data['images'][] = [
                                'image_url' => $request->getUriForPath('/media/'.$path.$imageFileName),
                            ]; //dd($data);
                        }
                    }
                }
            }
            if(empty($data['images'])) {
                if(isset($drag_url)){
                foreach($drag_url as $url){
                    $data['images'][] = [
                        'image_url' =>  $url,
                    ];
                }
                }
            }
            // if (isset($all_params['image_url']) && !empty($all_params['image_url'])) {
            //     foreach ($all_params['image_url'] as $image_url) {
            //         //check if base64 data : drag&drop case
            //         $imageUrl = $catalogHelper->base64ToUrl($image_url);
            //         if (!empty($imageUrl)) {
            //             $data['images'][] = [
            //                 'image_url' => $imageUrl,
            //             ];
            //         }
            //     }
            // }
            $data['categories'] = isset($all_params['categories']) ? $all_params['categories'] : [];

            if (isset($all_params['discount']) && isset($all_params['discount_type']) && !empty($all_params['discount']) && !empty($all_params['discount_type'])) {
                $data['discount'] = [
                    "type" => $all_params['discount_type'],
                    "value" => $all_params['discount']
                ];
            }
            if($company->getStorehash() == 'RishabhStore-SAAS727a' || $company->getStorehash() == 'VILLUMIb6f3') {
                list($product, $notifications, $updated, $productResponse) = $catalogHelper->update_product_vellum($data, $product->getProdId(), $product);
            } else {
                list($product, $notifications, $updated, $productResponse) = $catalogHelper->update_product($data, $product->getProdId(), $product);
            }
           
            
            if (isset($productResponse->message) && !empty($productResponse->message) ) {
                $notifications['danger'][] = ucfirst($productResponse->message);
            }
            
            $images = isset($productResponse->media->items) ? $productResponse->media->items : (object) array();
            
            if ($updated) {
                $notifications['success'][] = $this->translate->trans('message.common.record_updated_successfully');
            }

            if($company->getStorehash() == 'RishabhStore-SAAS727a' || $company->getStorehash() == 'VILLUMIb6f3') {
                foreach ($notifications as $type => $messages) {
                    foreach ($messages as $message) { 
                        $result['type'] = $type;
                        $result['message'] = $message;
                        if ($type == "success") {
                            $result['url'] = $this->generateUrl('wixmp_catalog_product_update', ['storeHash' => $companyApplication->getCompany()->getStoreHash(), 'product_id' => $product->getId(), 'active_tab' => $active_tab, "cc" => 1]);
                            $this->addFlash($type, $message);
                        }
                    }
                }
                return new JsonResponse($result);
            }

            foreach ($notifications as $type => $messages) {
                foreach ($messages as $message) {
                    $this->addFlash($type, $message);
                }
            }

            return $this->redirectToRoute('wixmp_catalog_product_update', ['storeHash' => $companyApplication->getCompany()->getStoreHash(), 'product_id' => $product->getId(), 'active_tab' => $active_tab, "cc" => 1]);
        }

        $categoryTree = $catalogHelper->getCategoryTree($companyApplication);
        if (empty($categoryTree)) {
            //$this->addFlash('warning', $this->translate->trans('message.product.add.please_choose_a_category'));
        }
        
        foreach ($notifications as $type => $messages) {
            foreach ($messages as $message) {
                $this->addFlash($type, $message);
            }
        }

        if(($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3') && $form->isSubmitted() && $formError = $this->getErrorMessages($form)) {
            foreach ($formError as $error) { 
                $result['type'] = 'danger';
                $result['message'] = $error;
            }
            return new JsonResponse($result);
        }
        $awardsItems = $catalogHelper->getAwards();
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header'        => true,
            'menu'          => true,
            'breadcrums'    => true,
            'template_name' => 'catalog/product_update',
            'title'         => $this->translate->trans('product').': '.$platform_product['name'],
            'form'          => $form->createView(),
            'company'       => $company,
            'product'       => $product,
            'api_product'   => $platform_product,
            'images'        => $images,
            'categoryTree'  => $categoryTree,
            'selected_cats' => isset($platform_product['collectionIds']) ? $platform_product['collectionIds'] : [],
            'plan_application_data' => $planApplicationData,
            'awardsItems' => $awardsItems,
        ]);
    }

    /**
     * @Route("/update_status/{entity_id}", name="update_status", methods="get")
     * @ParamConverter("product", options={"mapping": {"entity_id": "id"}})
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function update_status(CompanyApplication $companyApplication, Products $product, Request $request, CatalogHelper $catalogHelper)
    {
        $status_from = $request->query->all()['status_from'];
        $status_to = $request->query->all()['status_to'];
        $platform_product_id = $product->getProdId();

        if ($status_to == 'A') {
            $data['visible'] = true;
        } else {
            $data['visible'] = false;
        }

        $updated = false;
        $notifications = ['danger' => []];
        
        list($product, $notifications, $updated) = $catalogHelper->update_product($data, $product->getProdId(), $product);

        if ($updated) {
            $notifications['success'][] = $this->translate->trans('message.common.record_updated_successfully');
        }

        foreach ($notifications as $type => $messages) {
            foreach ($messages as $message) {
                $this->addFlash($type, $message);
            }
        }
        if ($request->get('current_url') != null) {
            return $this->redirect($request->get('current_url'));
        }

        return $this->redirectToRoute('wixmp_catalog_product_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
    }

    /**
     * @Route("/delete/{product_id}", name="delete")
     * @ParamConverter("product", options={"mapping": {"product_id": "id"}})
     * @Method("POST")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function delete(CompanyApplication $companyApplication, Products $product, Request $request, CatalogHelper $catalogHelper)
    {
        $response = array(
            'code' => 200,
            'notifications' => [],
        );
        
        $platform_product_id = $product->getProdId();
        list($notifications, $deleted) = $catalogHelper->delete_product($platform_product_id, $product);

        if ($deleted) {
            $response['notification'][] = ['type' => 'success', 'message' => $this->translate->trans('deleted_successfully')];
        } else {
            $response['code'] = 405;
            unset($notifications['verbose']);
            $storeHash = $companyApplication->getCompany()->getStoreHash();
            $delete_product_verbose_mode = $this->generateUrl('wixmp_catalog_product_delete_product_verbose', ['storeHash' => $storeHash, 'product_id' => $product->getId()]);
            $response['notification'][] = ['type' => 'error', 'message' => 'Product does not exists on your store. Do you want to delete this item ? (<a href="'.$delete_product_verbose_mode.'">Yes</a>)'];
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/delete/v_mode/{product_id}", name="delete_product_verbose")
     * @ParamConverter("product", options={"mapping": {"product_id": "id"}})
     * @Method("GET")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function delete_v_mode(CompanyApplication $companyApplication, Products $product, Request $request, CatalogHelper $catalogHelper): Response
    {
        $catalogHelper->delete_store_product($product);

        return $this->redirectToRoute('wixmp_catalog_product_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
    }

    /**
     * @Route("/sync", name="sync")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function syncProducts(Request $request, CompanyApplication $companyApplication, CatalogHelper $catalogHelper): Response
    {
        $response = $catalogHelper->sync_products($request, $companyApplication);
        return new JsonResponse($response);
    }

    /**
     * @Route("/delete/product/{product_id}/image", name="product_image_delete")
     * @ParamConverter("product", options={"mapping": {"product_id": "id"}})
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function deleteProductImage(
        CompanyApplication $companyApplication,
        Products $product,
        Request $request,
        CatalogHelper $catalogHelper,
        MediaHelper $mediaHelper
    )
    {
        $response = array(
            'code' => 200,
            'notifications' => [],
        );
        $platform_product_id = $request->get('platform_product_id');
        $image_id = $request->get('image_id');

        list($notifications, $deleted) = $catalogHelper->delete_product_image($platform_product_id, $image_id);
        if ($deleted) {
            
            $mediaId = $mediaHelper->getMediaMapByItem([
                "itemId" => $product->getId(),
                "ItemType" => "wixmp_product",
                "application" => $companyApplication->getApplication(),
                "company" => $companyApplication->getCompany(),
                "singleRecord" => true
            ]);
    
            $mediaId = !empty($mediaId) ? $mediaId->getId() : "";
            $mediaHelper->removeExtraData($mediaId);

            $cache = $this->cache;
            $catalogHelper->clear_cache(['platform_product_id' => $platform_product_id, 'clear_images' => true]);
            $notifications['success'][] = $this->translate->trans('message.product.image.deleted_successfully');
            $response['notification'][] = ['type' => 'success', 'message' => $this->translate->trans('deleted_successfully')];
        } else {
            $response['code'] = 405;
            $response['notification'][] = ['type' => 'error', 'message' => 'Original resource does not exists.'];
        }
        foreach ($notifications as $type => $messages) {
            foreach ($messages as $message) {
                $this->addFlash($type, $message);
            }
        }
        $current_url = $request->get('current_url');

        return new JsonResponse($response);

        //return $this->redirect($current_url.'?active_tab=images');
    }

    /**
     * @Route("/filter", name="filter")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * [used to provide dynamic result for filters]
     */
    public function filter(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->all();
        $params['company'] = $companyApplication->getCompany();
        
        $returnData = [];
        if (isset($params['filter_name']) && !empty($params['filter_name'])) {
            switch ($params['filter_name']) {
            case 'seller_id':
            case 'seller':

                if (isset($params['q'])) {
                    $params['name'] = $params['q'];
                }
                list($sellers, $params) = $sellerHelper->getSellersAsOption($params);
                $returnData = array(
                    'results' => $sellers->getItems(),
                    'page' => $params['page'],
                    'itemsPerPage' => $params['items_per_page'],
                    'totalCount' => $sellers->getTotalItemCount(),
                );
                break;
            }
        }
        
        return new JsonResponse($returnData);
    }

    private function getErrorMessages($form) {
        $errors = array();
        foreach ($form->getErrors() as $key => $error) {
            $template = $error->getMessageTemplate();
            $parameters = $error->getMessageParameters();
    
            foreach($parameters as $var => $value){
                $template = str_replace($var, $value, $template);
            }
    
            $errors[$key] = $template;
        }
        return $errors;
    }
}