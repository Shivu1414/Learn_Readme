<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\EtsyAuth;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\Products;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\CategoryMapping;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\EtsyCategories;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\WixCategories;
use Webkul\Modules\Wix\WixEtsyAppBundle\Utils\HelperClass;
use App\Entity\CompanyApplication;
use App\Utils\Platform\Wix\WixClient;

class CatalogHelper extends HelperClass
{
    public function get_products($params = [])
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $company = isset($params['company']) ? $params['company'] : $this->container->get('app.runtime')->get_company_application()->getCompany();

        $storeProduct_repo = $this->entityManager->getRepository(Products::class);
        $products = $storeProduct_repo->getProducts($params, $company);

        return [$products, $params];
    }

    public function get_product($params = [])
    {
        $productRepo = $this->entityManager->getRepository(Products::class);
        $productData = $productRepo->findOneBy($params); 

        return $productData;
    }

    public function sync_products($request, CompanyApplication $companyApplication)
    {
        $is_success = false;
        $notifications = [];
        $params = $request->request->all();
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (empty($params['limit'])) {
            $params['limit'] = 100;
        }
        
        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0
        $company = $companyApplication->getCompany();

        $product_repo = $this->entityManager->getRepository(Products::class);
        $product_count = $product_repo->getProductCount($company);
        
        $existingProductCount = isset($product_count[1]) ? $product_count[1] : 0; // already added products count
       
        $plan_prduct_count = 0; //initialize plan product count
        if ($companyApplication->getSubscription() != null) {
            $plan_features = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
            $plan_prduct_count = isset($plan_features['max_products']) ? $plan_features['max_products'] : 0;
           
        }
        
        if ($plan_prduct_count != 0 && $plan_prduct_count <= $existingProductCount) {
        
            $notifications[] = ['message' => $this->container->get('translator')->trans('plan_quota_exhausted'), 'type' => 'danger'];

            return array(
                'totalCount' => 0, /* no need to paginate further  */
                'items' => [],
                'notifications' => $notifications,
            );
        }

        $result = ['added' => 0, 'skipped' => 0];

        if ($start == 0) {
            //clear cache if exists to prevent duplicacy
            $this->cache->deleteitem('wixesty_products_data_'.$company->getId());
        }

        $hasCacheProductData = $this->cache->hasItem('wixesty_products_data_'.$company->getId());
        //get cahce id
        $cacheProductData = $this->cache->getItem('wixesty_products_data_'.$company->getId());
        $productsData = [];
        $requestApi = false;

        if (!$hasCacheProductData) {
            $requestApi = true;
            $productsData = array(
                'totalCount' => 0,
                'items' => [],
                'page' => 1,
            );
        } else {
            $productsData = $cacheProductData->get();
        }

        $batch = (int) $params['limit'] + (int) $start;
        
        if ($batch > $productsData['totalCount']) {
            $batch = $productsData['totalCount'];
        }
        
        if ($requestApi || (($batch <= $productsData['totalCount']) && ($batch > count($productsData['items'])))) {
            
            $platformHelper = $this->getAppHelper('platform');

            $apiParams = [
                'query' => [
                    'paging' => [
                        'limit' =>  $params['limit'],
                        'offset' => $params['page'] - 1,
                    ]
                ],
                'includeHiddenProducts' => true
                
            ];
            if ($params['page'] > 1) {
                $apiParams = [
                    'query' => [
                        'paging' => [
                            'limit' => $params['limit'],
                            'offset' =>($params['page'] -1) * $params['limit'],
                        ]
                    ],
                    'includeHiddenProducts' => true
                ];
            }
         
            list($response, $error) = $platformHelper->get_platform_products(
                $apiParams
            );
            
            $response = json_decode($response);
            if (isset($response->products) && !empty($response->products)) {
                $productsData['items'] = array_merge($productsData['items'], $response->products);
                ++$productsData['page'];
                $productsData['totalCount'] = isset($response->totalResults) ? $response->totalResults : count($response->products);
                
                //save to cache
                $cacheProductData->set($productsData);
                $isSaved = $this->cache->save($cacheProductData);
            } else {
                $notifications[] = array(
                    'type' => 'danger',
                    'message' => $this->translate->trans('message.wix.product.unable_to_import_product'),
                );
            }
        }

        $settingData = $platformHelper->getSettingValue([
            "company_application" => $companyApplication,
            "setting_name" => "shop"
        ]);

        $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
   
        // process batch
        $toProcessProducts = array_slice($productsData['items'], $start, $params['limit']);
        //$toProcessProducts = $productsData['items'];
        
        if (!empty($toProcessProducts)) {
            foreach ($toProcessProducts as $platform_product) {
                if ($plan_prduct_count == 0 || $plan_prduct_count > $existingProductCount) {

                    $isProductExists = $product_repo->isProductExists($company, $platform_product->id);
                    if (empty($isProductExists) && isset($platform_product->productType) && $platform_product->productType != 'digital') {
                        ++$result['added'];

                        //$productImage = isset($platform_product->media->mainMedia->thumbnail->url) ? $platform_product->media->mainMedia->thumbnail->url : "";
                        
                        $extraDetails = ['is_test_product' => $this->findTestInProductName($platform_product->name),];
                
                        $product = new Products();
                        $product->setWixProdId($platform_product->id);
                        $product->setCompany($company);
                        $product->setCompanyApplication($companyApplication);
                        $product->setUpdatedAt(time());
                        $product->setName($platform_product->name);
                        $product->setSku(isset($platform_product->sku) ? $platform_product->sku : null);
                        $product->setPrice(isset($platform_product->price->price) ? $platform_product->price->price : 0);
                        $product->setSyncStatus(2); // 2 For Not synced on etsy
                        $product->setShopId($defaultShopId);
                        $product->setExtraDetails(serialize($extraDetails));

                        $productMedia = property_exists($platform_product, "media") ? $platform_product->media : (object)[];
                        $productMedia = property_exists($productMedia, "mainMedia") ? $productMedia->mainMedia : (object)[];
                        $productThumb = property_exists($productMedia, "thumbnail") ? $productMedia->thumbnail : (object)[];
                        $productThumbUrl = property_exists($productThumb, "url") ? $productThumb->url : "";

                        $product->setImage($productThumbUrl);

                        $this->entityManager->persist($product);
                        // one more product added
                        ++$existingProductCount;
                    } else {
                        ++$result['skipped'];
                    }
                } else {
                    $notifications[] = ['message' => $this->container->get('translator')->trans("plan_quota_exhausted"), 'type' => 'danger'];
                    break; // limit exhust no need to loop for rest of the products
                }
            }
            $this->entityManager->flush();
        }

        $notifications[] = [
            'message' => $this->container->get('translator')->trans(
                'message.product.sync.success_%s_result%__skip_%sk_result%',
                array(
                    's_result' => $result['added'],
                    'sk_result' => $result['skipped'],
                )
            ),
            'type' => 'success',
        ];
        // clear cache on last batch
        if ($batch >= $productsData['totalCount']) {
            $this->cache->deleteitem('wixesty_products_data_'.$company->getId());
        }

        return array(
            'totalCount' => $productsData['totalCount'],
            'items' => $toProcessProducts,
            'notifications' => $notifications,
        );
    }

    public function onWebhookProductAdd($companyApplication, $productData)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $platformHelper = $this->getAppHelper("platform");
        $productId = property_exists($productData, "productId") ? $productData->productId : "";

        $wixClient = new WixClient($companyApplication);

        list($wixProduct, $error) = $wixClient->get_product($productId);
        $wixProductObj = json_decode($wixProduct);

        $platform_product = property_exists($wixProductObj, "product") ? $wixProductObj->product : (object)[];

        $product_repo = $this->entityManager->getRepository(Products::class);
        $isProductExists = $product_repo->isProductExists($companyApplication->getCompany(), $productId);

        if (empty($isProductExists) && isset($platform_product->productType) && $platform_product->productType != 'digital') {
            
            $product = new Products();
            $product->setWixProdId($platform_product->id);
            $product->setCompany($companyApplication->getCompany());
            $product->setCompanyApplication($companyApplication);
            $product->setUpdatedAt(time());
            $product->setName($platform_product->name);
            $product->setSku(isset($platform_product->sku) ? $platform_product->sku : "");
            $product->setPrice(isset($platform_product->price->price) ? $platform_product->price->price : 0);
            $product->setSyncStatus(2); // 2 For Not synced on etsy

            $productMedia = property_exists($platform_product, "media") ? $platform_product->media : (object)[];
            $productMedia = property_exists($productMedia, "mainMedia") ? $productMedia->mainMedia : (object)[];
            $productThumb = property_exists($productMedia, "thumbnail") ? $productMedia->thumbnail : (object)[];
            $productThumbUrl = property_exists($productThumb, "url") ? $productThumb->url : "";

            $product->setImage($productThumbUrl);
            
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            
            $settingData = $platformHelper->getSettingValue([
                "company_application" => $companyApplication,
                "setting_name" => "auto_sync_prots_etsy"
            ]);

            # auto sync to etsy
            if(!empty($settingData) && $settingData->getSettingValue()){
               $response = $platformHelper->importProductsOnEtsy($companyApplication, $request, ["prodIds" => [$product->getId()]]);
            } 
        }
    }

    public function onWebhookProductUpdate($companyApplication, $productData)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $platformHelper = $this->getAppHelper("platform");
        $productId = property_exists($productData, "productId") ? $productData->productId : "";
        
        $wixClient = new WixClient($companyApplication);

        list($wixProduct, $error) = $wixClient->get_product($productId);
        $wixProductObj = json_decode($wixProduct);

        $platform_product = property_exists($wixProductObj, "product") ? $wixProductObj->product : (object)[];

        $product_repo = $this->entityManager->getRepository(Products::class);
        $product = $this->get_product([
            "company_application" => $companyApplication,
            "wix_prod_id" => $productId
        ]);
        
        if (!empty($product) && isset($platform_product->productType) && $platform_product->productType != 'digital') {
            
            $product->setWixProdId($platform_product->id);
            $product->setCompany($companyApplication->getCompany());
            $product->setCompanyApplication($companyApplication);
            $product->setUpdatedAt(time());
            $product->setName($platform_product->name);
            $product->setSku(isset($platform_product->sku) ? $platform_product->sku : "");
            $product->setPrice(isset($platform_product->price->price) ? $platform_product->price->price : 0);
            
            $product->setSyncStatus(2); // 2 For Not synced on etsy

            $productMedia = property_exists($platform_product, "media") ? $platform_product->media : (object)[];
            $productMedia = property_exists($productMedia, "mainMedia") ? $productMedia->mainMedia : (object)[];
            $productThumb = property_exists($productMedia, "thumbnail") ? $productMedia->thumbnail : (object)[];
            $productThumbUrl = property_exists($productThumb, "url") ? $productThumb->url : "";

            $product->setImage($productThumbUrl);
            
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            
            //$platformHelper->importProductsOnEtsy($companyApplication, $request, ["prodIds" => [$product->getId()]]);
        }
    }
    public function onWebhookProductDelete($product_id, $companyApplication)
    {   
        try {
            $storeProduct_repo = $this->entityManager->getRepository(Products::class); 
            $productData = $storeProduct_repo->findOneBy(['wix_prod_id' => $product_id]); 
            if (!empty($productData)) {
                if (!$this->entityManager->contains($productData)) {
                    $productData = $this->entityManager->merge($productData);
                }
                $this->entityManager->remove($productData);
                $this->entityManager->flush();
            }
            
        } catch (DBALException $e) {
            $sql_error_code = $e->getPrevious()->getCode();
            if ($sql_error_code == '23000') {
                
                $this->logger->alert('******************** Webhook Error ********************');
                $this->logger->alert("Can not delete product already in use. ProdId: ". $product_id);

            } else {
                $this->logger->alert('******************** Webhook Error ********************');
                $this->logger->alert("Can not delete product. ProdId: ". $product_id);
            }
        }
    }
    public function performBatchAction($request, $formData, $companyApplication)
    {
        $notifications = [];
        $action = $formData['batch_action'];
        $productIds = $request->request->get('product_ids');
        $company = $companyApplication->getCompany();
        if (empty($productIds)) {
            $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.empty_data_for_bulk_action')];

            return $notifications;
        }

        $extraParams['batchAction'] = $action;
        $platformHelper = $this->getAppHelper("platform");

        switch ($action) {

            case 'import_to_etsy':
                $importProductResponse = $platformHelper->importProductsOnEtsy($companyApplication, ["prodIds" => $productIds]);
                //dd($importProductResponse);
            break;

            default:
                $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.invalid_bulk_action')];
            break;
        }

        return $notifications;
    }

    public function arrangeCategoryMapping($wixCategorySelected, $etsyCategorySelected)
    {   
        $mappedCategory = [];

        for ($i = 0; $i < count($wixCategorySelected); $i++) {

            // $mappedCategory[] = [
            //     $wixCategorySelected[$i] => [
            //         isset($etsyCategorySelected[$i]) ? $etsyCategorySelected[$i] : ""
            //     ]
            // ];

            if ( isset($mappedCategory[$wixCategorySelected[$i]]) ) {
                array_push($mappedCategory[$wixCategorySelected[$i]],$etsyCategorySelected[$i]);
            } else {
                $mappedCategory[$wixCategorySelected[$i]] = [
                    isset($etsyCategorySelected[$i]) ? $etsyCategorySelected[$i] : ""
                ];
            }
        }
        return $mappedCategory;
    }

    public function addCategoryMapping($companyApplication, $arrangedCategoryMapping)
    {
        $platformHelper = $this->getAppHelper("platform");

        // $defaultShop = $platformHelper->getEtsyShopData([
        //     "company_application" => $companyApplication,
        //     "is_default" => 1
        // ]);

        // $defaultShopId = !empty($defaultShop) ? $defaultShop->getShopId() : "";

        $settingData = $platformHelper->getSettingValue([
            "company_application" => $companyApplication,
            "setting_name" => "shop"
        ]);

        $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
        
        foreach ($arrangedCategoryMapping as $wixCategory => $etsyCategories) {

            $wixCategory = $this->getWixCategory([
                "category_id" => $wixCategory
            ]);

            foreach ($etsyCategories as $etsyCategory) {

                $etsyCategory = $this->getEtsyCategory([
                    "category_id" => $etsyCategory
                ]);
                 
                $isCategoryMappingExist = $this->getCategoryMapping([
                    "company" => $companyApplication->getCompany(),
                    "company_application" => $companyApplication,
                    "wix_category" => $wixCategory,
                    "etsy_category" => $etsyCategory,
                    "shop_id" => $defaultShopId
                ]);

                if (empty($isCategoryMappingExist)) {

                    $categoryMapping = new CategoryMapping;

                    $categoryMapping->setCompany($companyApplication->getCompany());
                    $categoryMapping->setCompanyApplication($companyApplication);
                    $categoryMapping->setWixCategory($wixCategory);
                    $categoryMapping->setEtsyCategory($etsyCategory);
                    $categoryMapping->setUpdatedAt(time());
                    $categoryMapping->setShopId($defaultShopId);

                    $this->entityManager->persist($categoryMapping);
                    $this->entityManager->flush();
                }
            }

        }

        return [
            "type" => "success",
            "message" => "category_mapping_added_successfully"
        ];

    }

    public function getCategoryMapping($params = [])
    {
        $categoryMappingRepo = $this->entityManager->getRepository(CategoryMapping::class);
        $categoryMapping = $categoryMappingRepo->findOneBy($params); 

        return $categoryMapping;
    }

    public function getCategoryMappings($params = [])
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $company = isset($params['company']) ? $params['company'] : $this->container->get('app.runtime')->get_company_application()->getCompany();

        $categoryMappingRepo = $this->entityManager->getRepository(CategoryMapping::class);
        $categoryMappings = $categoryMappingRepo->getCategoryMappings($params, $company);

        return [$categoryMappings, $params];
    }

    public function updateEtsyCategories($companyApplication, $categories = [])
    {
        $platformHelper = $this->getAppHelper('platform');
        
        $settingData = $platformHelper->getSettingValue([
            "company_application" => $companyApplication,
            "setting_name" => "shop"
        ]);

        $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";

        foreach ($categories as $category) {

            $this->addCategory($companyApplication , $category , $defaultShopId);
            if(isset($category->children) && !empty($category->children)){
                $this->iteration($category->children,$defaultShopId,$companyApplication,$category->name);
            }
        }
        $this->entityManager->flush();

        return [
            "type" => "success",
            "message" => "etsy_category_updated_successfully"
        ];
    }

  public function iteration($subCategory, $defaultShopId, $companyApplication, $categoryName){
    
    foreach($subCategory as $categoryChildren){
        $categoryChildren->name = $categoryName .'/'. $categoryChildren->name; 
        $this->addCategory($companyApplication , $categoryChildren, $defaultShopId);
        
        if($categoryChildren->children){
            $this->iteration($categoryChildren->children, $defaultShopId, $companyApplication, $categoryChildren->name);
        }
    }
  }


    public function addCategory($companyApplication, $category , $defaultShopId){

        $etsyCategory = $this->getEtsyCategory([
            "company_application" => $companyApplication,
            "category_id" => property_exists($category, "id") ? $category->id : "",
            "shop_id" => $defaultShopId
        ]);
        $etsyCategory = !empty($etsyCategory) ? $etsyCategory : new EtsyCategories;

        $etsyCategory->setCompany($companyApplication->getCompany());
        $etsyCategory->setCompanyApplication($companyApplication);
        $etsyCategory->setCategoryId(property_exists($category, "id") ? $category->id : "");
        $etsyCategory->setCategory(property_exists($category, "name") ? $category->name : "");
        $etsyCategory->setShopId($defaultShopId);
        $etsyCategory->setUpdatedAt(time());

        $this->entityManager->persist($etsyCategory);
    }

    public function updateWixCategories($companyApplication, $categories = [])
    {
        foreach ($categories as $category) {
            
            if (property_exists($category, "id") && $category->id != "00000000-000000-000000-000000000001") {
                
                $wixCategory = $this->getWixCategory([
                    "company_application" => $companyApplication,
                    "category_id" => property_exists($category, "id") ? $category->id : ""
                ]);

                $wixCategory = !empty($wixCategory) ? $wixCategory : new WixCategories;
                
                $wixCategory->setCompany($companyApplication->getCompany());
                $wixCategory->setCompanyApplication($companyApplication);
                $wixCategory->setCategoryId(property_exists($category, "id") ? $category->id : "");
                $wixCategory->setCategory(property_exists($category, "name") ? $category->name : "");
                $wixCategory->setUpdatedAt(time());

                $this->entityManager->persist($wixCategory);
            }
        }

        $this->entityManager->flush();
        return [
            "type" => "success",
            "message" => "wix_category_updated_successfully"
        ];
    }

    public function getEtsyCategory($params = [])
    {
        $etsyCategoryRepo = $this->entityManager->getRepository(EtsyCategories::class);
        $etsyCategory = $etsyCategoryRepo->findOneBy($params); 

        return $etsyCategory;
    }

    public function getEtsyCategories($params = [])
    {
        $etsyCategoryRepo = $this->entityManager->getRepository(EtsyCategories::class);
        $etsyCategories = $etsyCategoryRepo->findBy($params); 

        return $etsyCategories;
    }

    public function getWixCategory($params = [])
    {
        $wixCategoryRepo = $this->entityManager->getRepository(WixCategories::class);
        $wixCategory = $wixCategoryRepo->findOneBy($params); 

        return $wixCategory;
    }

    public function getWixCategories($params = [])
    {
        $wixCategoryRepo = $this->entityManager->getRepository(WixCategories::class);
        $wixCategories = $wixCategoryRepo->findBy($params); 

        return $wixCategories;
    }

    public function updateEtsyCategory($companyApplication, $etsyCategory, $params = [])
    {
        $etsyCategory = !empty($etsyCategory) ? $etsyCategory : new EtsyCategories;

        $etsyCategory->setUpdatedAt(time());

        $em = $this->entityManager;
        $em->persist($etsyCategory);
        $em->flush();

        return $etsyCategory;
    }

    public function deleteCategoryMapping($categoryMappingId)
    {
        try {
            $categoryMappingRepo = $this->entityManager->getRepository(CategoryMapping::class);
            $categoryMapping = $categoryMappingRepo->findOneBy(['id' => $categoryMappingId]);
        
            //$this->entityManager->persist($productData);
            $this->entityManager->remove($categoryMapping);
            $this->entityManager->flush();

            return [
                "type" => "success",
                "message" => "category_mapping_deleted_successfully"
            ];
            
        } catch (DBALException $e) {

            return [
                "type" => "danger",
                "message" => "cannot_delete_category_mapping"
            ];
        }
    }
    public function findTestInProductName($productName) {
        $productName = strtolower($productName); // Convert to lowercase for case-insensitive comparison
        
        if (strpos($productName, " test ") !== false) {
            // Check if " test " is present anywhere in the string
            return true;
        }
        
        if (strpos($productName, "test ") === 0) {
            // Check if "test " is at the beginning of the string
            return true;
        }
        
        if (strpos($productName, " test") !== false && strpos($productName, " test") === (strlen($productName) - strlen(" test"))) {
            // Check if " test" is at the end of the string
            return true;
        }
        
        return false;
    }

    public function onWebhookCollectionAdd($companyApplication, $collectionData)
    {
        $wixCategories_repo = $this->entityManager->getRepository(WixCategories::class);
        $isWixCategoriesSynced = $wixCategories_repo->findBy(['company' => $companyApplication->getCompany()]); 
        #webhook will only work if categories are once synced
        if(!empty($isWixCategoriesSynced)){
            $isWixCategoryExists = $wixCategories_repo->findOneBy([
                'company' => $companyApplication->getCompany(),
                'category_id' => $collectionData->collectionId
            ]);

            $category = !$isWixCategoryExists ? new WixCategories() : $isWixCategoryExists ;
            $category->setCompany($companyApplication->getCompany());
            $category->setCompanyApplication($companyApplication);
            $category->setCategoryId($collectionData->collectionId);
            $category->setUpdatedAt((int)time());
            $category->setCategory($collectionData->name);

            $this->entityManager->persist($category);
            $this->entityManager->flush();
            
            return true;
        }
        return true;
    }

    public function onWebhookCollectionUpdate($companyApplication, $collectionData)
    { 
        $wixCategories_repo = $this->entityManager->getRepository(WixCategories::class);
        $isWixCategoriesSynced = $wixCategories_repo->findBy(['company' => $companyApplication->getCompany()]);
       
        #webhook will only work if categories are once synced
        if(!empty($isWixCategoriesSynced)){
            
            $wixclientOBJ = new WixClient($companyApplication);
            $platformCategory=  $wixclientOBJ->get_collection($params = [
                'id' => $collectionData->collectionId
            ]);
            $updatedName = json_decode($platformCategory[0])->collection->name;

            $isWixCategoryExists = $wixCategories_repo->findOneBy([
                'company' => $companyApplication->getCompany(),
                'category_id' => $collectionData->collectionId
            ]);

            $category = !$isWixCategoryExists ? new WixCategories() : $isWixCategoryExists ;
            $category->setCompany($companyApplication->getCompany());
            $category->setCompanyApplication($companyApplication);
            $category->setCategoryId($collectionData->collectionId);
            $category->setUpdatedAt((int)time());
            $category->setCategory($updatedName);

            $this->entityManager->persist($category);
            $this->entityManager->flush();
            
            return true;
        } 
        return true;
    }

    public function onWebhookCollectionDelete($companyApplication, $collectionData)
    { 
        $wixCategories_repo = $this->entityManager->getRepository(WixCategories::class);
        $isWixCategoriesSynced = $wixCategories_repo->findBy(['company' => $companyApplication->getCompany(),]);
       
        #webhook will only work if categories are once synced
        if(!empty($isWixCategoriesSynced)){
            $category = $wixCategories_repo->findOneBy([
                'company' => $companyApplication->getCompany(),
                'category_id' => $collectionData->collectionId
            ]);

            if($category){
                $this->entityManager->remove($category);
                $this->entityManager->flush();
                return true;
            }
        }
        return true;
    }
}