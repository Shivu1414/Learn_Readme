<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\CatalogController;

use App\Core\BaseController;
use App\Helper\CommonHelper;
use App\Entity\CompanyApplication;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Webkul\Modules\Wix\WixmpBundle\Form\Catalog\ProductsType;
use Webkul\Modules\Wix\WixmpBundle\Utils\CatalogHelper;
use Webkul\Modules\Wix\WixmpBundle\Events\CatalogEvent;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Webkul\Modules\Wix\WixmpBundle\Utils\PlatformHelper;
use App\Helper\MediaHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/catalog", name="wixmp_seller_catalog_product_")
 * @Security("is_granted('ROLE_WIXMP_SELLER')")
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
     * @Route("/", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function product_manage(Request $request, CompanyApplication $companyApplication, CatalogHelper $catalogHelper, CommonHelper $commonHelper)
    {
        $form = $this->createForm(ProductsType::class);
        $form->handleRequest($request);
        $seller = $this->getUser()->getSeller();
            
        if ($form->isSubmitted() && $form->isValid()) {
            
            $formData = $form->getData(); 
            $requestData = $request->request->all();
            if (isset($formData['batch_action'])) {
                // dd($request);
                $notifications = $catalogHelper->performBatchAction($request, $formData, $companyApplication, $seller);
                if (!empty($notifications)) {
                    foreach ($notifications as $notification) {
                        $this->addFlash($notification['type'], $notification['message']);
                    }
                }
            }
        }

        $page = $request->get('page');
        if ($page == null) {
            $page = 1;
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
        $company = $companyApplication->getCompany();
        $params['company'] = $company;
        $params['seller'] = null;
        if ($request->get('area') == 'mp-wix-seller') {
            $params['seller'] = $this->getUser()->getSeller();
        }
        $setting_data = $commonHelper->get_section_setting([
            'settingName' => 'auto_approve_product',
            'company'     => $companyApplication->getCompany(),
            'application' => $companyApplication->getApplication()
        ]);
      
        list($products, $params) = $catalogHelper->get_products($params); 
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'breadcrums_template' => 'common/breadcrums',
            'template_name' => 'catalog/product_manage',
            'title' => 'product',
            'list_count' => $products->getTotalItemCount(),
            'products' => $products,
            'filter' => $params,
            'search' => $request->query->all(),
            'companyApplication' => $companyApplication,
            'form' => $form->createView(),
            'seller' => $seller,
            'setting' => $setting_data,
        ]);
    }

    /**
     * @Route("/add", name="add")
     * @Method("GET|POST")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function add(CompanyApplication $companyApplication, Request $request, CatalogHelper $catalogHelper, CommonHelper $commonHelper, PlatformHelper $platformHelper, MediaHelper $mediaHelper): Response
    {
        $catalogEvent = new CatalogEvent($companyApplication);
        $company = $companyApplication->getCompany();

        $planApplicationData = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();

        $this->event_dispatcher->dispatch(
            $catalogEvent, 
            CatalogEvent::CATALOG_PRODUCT_WIX_SELLER_ADD
        );
        
        if ($catalogEvent->getActionAllowed() == 'N') {
            return $this->redirectToRoute('wixmp_seller_catalog_product_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }
        $seller = $this->getUser()->getSeller();

        $params['settingName'] = 'seller_allowed_categories';
        $params['company'] = $companyApplication->getCompany();
        $params['application'] = $companyApplication->getApplication();
        $setting_data = $commonHelper->get_section_setting($params);
        
        $allowedCategories = [];
        if (!empty($setting_data)) {
            $allowedCategories = !empty($setting_data->getValue())?explode(',', $setting_data->getValue()):[]; 
        }
        // seller specific allowed categories 
        if (!empty($seller->getAllowedCategories())) {
            $allowedCategories = $seller->getAllowedCategories();
        }

        $notifications = [];
        $added = false;
        $platform_product = [];
    
        if($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3') {
            $form = $catalogHelper->getProductFormVillum($platform_product, null);
        } else {
            $form = $catalogHelper->getProductForm($platform_product, null);
        }

        
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData(); 
            $all_params = $request->request->all()['form'];
            $catalogEvent = new CatalogEvent($companyApplication);
            $catalogEvent->setProductData($data);
            $catalogEvent->setProductParams($all_params);
            $this->event_dispatcher->dispatch(
                $catalogEvent,
                CatalogEvent::CATALOG_PRODUCT_WIX_SELLER_ADD
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
            } else{
                list($platform_product_data, $notifications) = $catalogHelper->arrangeUpdatePlatformProduct($data, $all_params); 
            }
            $drag_images['images'] = $platform_product_data['images'];
            foreach($drag_images['images'] as $image) {
                $drag_url[] = $image['image_url']."/images.jpg";
            }

            $params['settingName'] = 'auto_approve_product';
            $params['company'] = $companyApplication->getCompany();
            $params['application'] = $companyApplication->getApplication();
            //$setting_data = $commonHelper->get_section_setting($params);
            $setting_data = $commonHelper->get_section_setting([
                'settingName' => 'auto_approve_product',
                'company'     => $companyApplication->getCompany(),
                'application' => $companyApplication->getApplication()
            ]);
            
            if ($setting_data != null && $setting_data->getValue() == 'Y') {
                $platform_product_data['product']['visible'] = true;
            } elseif($setting_data != null && $setting_data->getValue() == 'N') {
                $platform_product_data['product']['visible'] = false;
                $platform_product_data['status_to'] = 'N';
            } else {
                $platform_product_data['product']['visible'] = true;
                $platform_product_data['status_to'] = 'Y';
            }
           
            // if product image required : check validity
            if (isset($sellerSettingData['product_image_required']) && $sellerSettingData['product_image_required']->getValue()) {
                if (!isset($all_params['image_url']) || empty($all_params['image_url'])) {
                    $notifications['danger'][] = $this->translate->trans('required_image_error_msg');
                }
            }

            $extraParams = [];
            $extraParams['categories'] = isset($all_params['categories']) ? $all_params['categories'] : [];

            $identifierPosition = $commonHelper->get_section_setting([
                'settingName' => 'products_identifier_position', 
                'company' => $companyApplication->getCompany(), 
                'application' => $companyApplication->getApplication()
            ]);

            $identifierPositionValue = $commonHelper->get_section_setting([
                'settingName' => 'products_identifier_position_value', 
                'company' => $companyApplication->getCompany(), 
                'application' => $companyApplication->getApplication()
            ]);

            
            $identifierJoinerValue = $commonHelper->get_section_setting([
                'settingName' => 'products_identifier_joiner_value', 
                'company' => $companyApplication->getCompany(), 
                'application' => $companyApplication->getApplication()
            ]);
            
            $identifierJoinerProductEnable = $commonHelper->get_section_setting([
                'settingName' => 'products_identifier_enable_product', 
                'company' => $companyApplication->getCompany(), 
                'application' => $companyApplication->getApplication()
            ]);

            $ifjp = '';
            if (isset($identifierJoinerValue)) {
                $ifjp = $identifierJoinerValue->getValue();
            }

            $extraParams['originalName'] = $platform_product_data['product']['name'];

            if (isset($identifierJoinerProductEnable) && $identifierJoinerProductEnable->getValue() == 1 && isset($seller)) {
                $sellerName = $seller->getSeller();
                $sellerId = $seller->getId();
                if (isset($identifierPosition) && $identifierPosition->getValue() == 'P') {
                    if (isset($identifierPositionValue) && $identifierPositionValue->getValue() == 'SN') {
                        $platform_product_data['product']['name'] = $sellerName.' '.$ifjp.' '.$platform_product_data['product']['name'];
                    }
                } elseif (isset($identifierPosition) && $identifierPosition->getValue() == 'S') {
                    if (isset($identifierPositionValue) && $identifierPositionValue->getValue() == 'SN') {
                        $platform_product_data['product']['name'] = $platform_product_data['product']['name'].' '.$ifjp.' '.$sellerName;
                    }
                }
            }
            
            if(isset($company) && ($company->getStorehash() == 'RishabhStore-SAAS727a' || $company->getStorehash() == 'VILLUMIb6f3') ) {
                list($isSuccess, $productResponse, $product) = $catalogHelper->create_product_vellum(
                    $companyApplication, $platform_product_data, $data, $seller, $extraParams
                );
                if (isset($productResponse->message) && !empty($productResponse->message)) {
                    $result['type'] = 'danger';
                    $result['message'] = $productResponse->message;
                    return new JsonResponse($result);
                }
            } else {
                list($isSuccess, $productResponse, $product) = $catalogHelper->create_product(
                    $companyApplication, $platform_product_data, $data, $seller, $extraParams
                );
            }
            if (isset($productResponse->message) && !empty($productResponse->message)) {
                $this->addFlash('danger', ucfirst($productResponse->message));
            }
            if ($isSuccess) {
               
                $productId = isset($productResponse->product->id) ? $productResponse->product->id : "";
                // if (isset($data['images']) && !empty($data['images'])) {
                //     foreach($data['images'] as $image) {
                //         $mediaData['media'] = [
                //             [
                //                 "url" => isset($image['image_url']) ? $image['image_url'] : "",
                //             ]
                //         ];
                //         list($response, $error) = $platformHelper->add_product_media($productId, $mediaData);
                //     }
                // }

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
                                $result['url'] = $this->generateUrl("wixmp_seller_catalog_product_manage",[
                                    'storeHash' => $request->get('storeHash')
                                ]);
                                $this->addFlash($type, $message);
                            }
                        }
                    }
                    !isset($result) ? $result = ['type' => 'success', 'message' => $this->translate->trans('message.product.added_success'), 'url' => $this->generateUrl("wixmp_seller_catalog_product_manage",[
                        'storeHash' => $request->get('storeHash')
                    ] )] : '';
                    return new JsonResponse($result);
                }

                $this->addFlash('success', $this->translate->trans('message.product.added_success'));
                return $this->redirectToRoute("wixmp_seller_catalog_product_manage",[
                    'storeHash' => $request->get('storeHash')
                ]);
            }
        }
        
        $categoryTree = $catalogHelper->getCategoryTree($companyApplication, false, $allowedCategories);
        if (empty($categoryTree)) {
            //$this->addFlash('warning', $this->translate->trans('message.product.add.please_choose_a_category'));
        }

        $params['settingName'] = 'product_discount';
        $params['company'] = $companyApplication->getCompany();
        $params['application'] = $companyApplication->getApplication();
        $setting_data = $commonHelper->get_section_setting($params);

        $isProductDiscountForSeller = !empty($setting_data) ? $setting_data->getValue() : "";

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
            'title' => $this->translate->trans('product').': '.$this->translate->trans('add_product_page'),
            'product' => $platform_product,
            'api_product' => $platform_product,
            'company' => $company,
            'form' => $form->createView(),
            'categoryTree' => $categoryTree,
            'selected_cats' => [],
            'isProductDiscountForSeller' => $isProductDiscountForSeller,
            'plan_application_data' => $planApplicationData,
            'awardsItems' => $awardsItems,
        ]);
    }

    /**
     * @Route("/update/{product_id}", name="update")
     * @ParamConverter("product", options={"mapping": {"product_id": "id"}})
     * @Method("GET|POST")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function update(CompanyApplication $companyApplication, Products $product, Request $request, CatalogHelper $catalogHelper, CommonHelper $commonHelper, MediaHelper $mediaHelper): Response
    {   
        $updated = false;
        $notifications = [];
        $company = $companyApplication->getCompany();
        $planApplicationData = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
        $sellerSettingData = $commonHelper->get_section_setting(['sectionName' => 'seller', 'company' => $companyApplication->getCompany(), 'application' => $companyApplication->getApplication()], true);

        $seller = $this->getUser()->getSeller();

        $cache = $this->cache;
        $productCacheList = $cache->getItem('product_data_'.$product->getProdId());
        
        //if (!$productCacheList->isHit()) {
            $params = [];
            list($platform_product_raw, $notifications) = $catalogHelper->get_product($product->getProdId(), $params);
            
            foreach ($notifications as $type => $messages) {
                foreach ($messages as $message) {
                    $this->addFlash($type, $message);
                }
            }
            if (empty($platform_product_raw)) {
                return $this->redirectToRoute('wixmp_seller_catalog_product_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
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
            $productCacheList->expiresAfter(300);  // data saved only for 300 seconds Increase if necessary
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
        if($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3') {
            $form = $catalogHelper->getProductFormVillum($platform_product, null);
        } else {
            $form = $catalogHelper->getProductForm($platform_product, null);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() ) {
            
            $platform_product_id = $platform_product['id'];
            $data = $form->getData();
            $all_params = $request->request->all()['form'];
            $active_tab = $request->request->all()['active_tab'];
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
            
            if($company->getStoreHash() == 'RishabhStore-SAAS727a' || $company->getStoreHash() == 'VILLUMIb6f3' ) {
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
                            ];
                        }
                    }
                }
            }
            if(empty($data['images']) && isset($drag_url)) {
                foreach($drag_url as $url){
                    $data['images'][] = [
                        'image_url' =>  $url,
                    ];
                }
            }
            $catalogEvent = new CatalogEvent($companyApplication);
            $catalogEvent->setProductData($data);
            $catalogEvent->setProductParams($all_params);
            $this->event_dispatcher->dispatch(
                $catalogEvent, CatalogEvent::CATALOG_PRODUCT_WIX_SELLER_UPDATE
            );

            $data = $catalogEvent->getProductData();
            $all_params = $catalogEvent->getProductParams();
            if ($data['trackInventory']){    
                if (!preg_match("/^(0|[1-9]\d{0,4}|99999)$/", $data['quantity'])) {
                    $this->addFlash("danger",$this->translate->trans('inventory_quantity_validation'));
                    return $this->redirectToRoute('wixmp_catalog_product_update', ['storeHash' => $companyApplication->getCompany()->getStoreHash(), 'product_id' => $product->getId(), 'active_tab' => $active_tab, "cc" => 1]);
                }
            }
            $params['settingName'] = 'auto_approve_product';
            $params['company'] = $companyApplication->getCompany();
            $params['application'] = $companyApplication->getApplication();
            //$setting_data = $commonHelper->get_section_setting($params);
            $setting_data = $commonHelper->get_section_setting([
                'settingName' => 'auto_approve_product',
                'company'     => $companyApplication->getCompany(),
                'application' => $companyApplication->getApplication()
            ]);
            
            if ($setting_data != null && $setting_data->getValue() == 'Y') {
                $data['visible'] = true;
            } else {
                $data['visible'] = false;
                $data['status_to'] = 'N';
            }

            $data['categories'] = isset($all_params['categories']) ? $all_params['categories'] : [];

            if (
                isset($all_params['discount']) &&
                isset($all_params['discount_type']) &&
                !empty($all_params['discount']) &&
                !empty($all_params['discount_type'])
            ) {
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
            //list($product, $notifications, $updated, $productResponse) = $catalogHelper->update_product($data, $product->getProdId(), $product, $seller);

            $images = isset($productResponse->media->items) ? $productResponse->media->items : (object) array();

            if (isset($productResponse->message) && !empty($productResponse->message) ) {
                $notifications['danger'][] = ucfirst($productResponse->message);
            }
            
            if ($updated) {
                $notifications['success'][] = $this->translate->trans('message.common.record_updated_successfully');
            }

            if($company->getStorehash() == 'RishabhStore-SAAS727a' || $company->getStorehash() == 'VILLUMIb6f3') {
                foreach ($notifications as $type => $messages) {
                    foreach ($messages as $message) { 
                        $result['type'] = $type;
                        $result['message'] = $message;
                        if ($type == "success") {
                            $result['url'] = $this->generateUrl('wixmp_seller_catalog_product_update', ['storeHash' => $companyApplication->getCompany()->getStoreHash(), 'product_id' => $product->getId(), 'active_tab' => $active_tab]);
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
            $active_tab = $request->request->all()['active_tab'];
            return $this->redirectToRoute('wixmp_seller_catalog_product_update', ['storeHash' => $companyApplication->getCompany()->getStoreHash(), 'product_id' => $product->getId(), 'active_tab' => $active_tab]);
        }

        $allowedCategories = [];
        if (isset($sellerSettingData['seller_allowed_categories']) && !empty($sellerSettingData['seller_allowed_categories']->getValue())) {
            $allowedCategories = explode(',', $sellerSettingData['seller_allowed_categories']->getValue());
        }
        // seller specific allowed categories 
        if (!empty($seller->getAllowedCategories())) {
            $allowedCategories = $seller->getAllowedCategories();
        }

        $categoryTree = $catalogHelper->getCategoryTree($companyApplication, false, $allowedCategories);
        if (empty($categoryTree)) {
            //$this->addFlash('warning', $this->translate->trans('message.product.add.please_choose_a_category'));
        }

        $params['settingName'] = 'product_discount';
        $params['company'] = $companyApplication->getCompany();
        $params['application'] = $companyApplication->getApplication();
        $setting_data = $commonHelper->get_section_setting($params);

        $isProductDiscountForSeller = !empty($setting_data) ? $setting_data->getValue() : "";

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
        return $this->render(
            '@wixmp_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'catalog/product_update',
                'title' => $this->translate->trans('product').': '.$platform_product['name'],
                'product' => $product,
                'api_product' => $platform_product,
                'company' => $company,
                'form' => $form->createView(),
                'seller' => $seller,
                'images' => $images,
                'categoryTree' => $categoryTree,
                'selected_cats' => isset($platform_product['collectionIds']) ? $platform_product['collectionIds'] : [],
                'isProductDiscountForSeller' => $isProductDiscountForSeller,
                'plan_application_data' => $planApplicationData,
                'awardsItems' => $awardsItems,
            ]
        );
    }

    /**
     * @Route("/delete/{product_id}", name="delete")
     * @ParamConverter("product", options={"mapping": {"product_id": "id"}})
     * @Method("POST")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function delete(CompanyApplication $companyApplication, Products $product, Request $request, CatalogHelper $catalogHelper): Response
    {
        $response = array(
            'code' => 200,
        );
        $platform_product_id = $product->getProdId();
        list($notifications, $deleted) = $catalogHelper->delete_product($platform_product_id, $product);
        if ($deleted) {
            $cache = $this->cache;
            $cache->deleteItem('product_data_'.$platform_product_id.'_images');
            $response['notification'] = ['type' => 'success', 'message' => $this->translate->trans('deleted_successfully')];
        } else {
            $response['code'] = 405;
            unset($notifications['verbose']);
            $storeHash = $companyApplication->getCompany()->getStoreHash();
            $delete_product_verbose_mode = $this->generateUrl('wixmp_seller_catalog_product_delete_product_verbose', ['storeHash' => $storeHash, 'product_id' => $product->getId()]);
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

        return $this->redirectToRoute('wixmp_seller_catalog_product_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
    }

    /**
     * @Route("/update_approval/{entity_id}", name="update_status", methods="get")
     * @ParamConverter("product", options={"mapping": {"entity_id": "id"}})
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function update_status(CompanyApplication $companyApplication, Products $product, Request $request, CatalogHelper $catalogHelper,CommonHelper $commonHelper): Response
    {
        $status_from = $request->query->all()['status_from'];
        $status_to = $request->query->all()['status_to'];
        $setting_data = $commonHelper->get_section_setting([
            'settingName' => 'auto_approve_product',
            'company'     => $companyApplication->getCompany(),
            'application' => $companyApplication->getApplication()
        ]);
    
        // dd($setting_data->getValue(),$status_from);
        $platform_product_id = $product->getProdId();
        if(isset($setting_data) and ($setting_data->getValue() == 'N' and ($status_from == 'D')))
        {
            $this->addFlash('danger', $this->translate->trans('message.you_not_allowed'));
        } else {
            if ($status_to == 'A') {
                $data['visible'] = true;
            } else {
                $data['visible'] = false;
            }
        }
        $data['status_to'] = $status_to;
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

        return $this->redirect($current_url.'?active_tab=images');
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
