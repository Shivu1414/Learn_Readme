<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\EtsyAuth;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\EtsyShop;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\ShippingProfiles;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\Products;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\Orders;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\SettingValue;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\WixEtsyProductImageMapping;
use App\Entity\CompanyApplication;
use App\Utils\Platform\Wix\WixClient;

class PlatformHelper extends HelperClass
{
    public function etsyAuthentication($companyApplication, $params = [])
    { 
        # Step 1
        list($verifier, $code_challenge) = $this->generateEtsyChallengeCode(); 
        $nonce = $this->createEtsyNonce(); 
    
        $scopes = ["address_r", "address_w", "billing_r", "cart_r", "cart_w", "email_r", "favorites_r", "favorites_w", "feedback_r", "listings_d", "listings_r", "listings_w", "profile_r", "profile_w", "recommend_r", "recommend_w", "shops_r", "shops_w", "transactions_r", "transactions_w"];
        
        $scope = implode(" ",array_map("trim", array_filter($scopes)));
        
        $dataToSend = [
            "response_type" => "code",
            "client_id" => isset($params['keyString']) ? $params['keyString'] : "",
            "scope" => $scope,
            "redirect_uri" => isset($params['redirectUri']) ? $params['redirectUri'] : "",
            "code_challenge" => $code_challenge,
            "state" => base64_encode($companyApplication->getCompany()->getStoreHash()),
            "code_challenge_method" => "S256"
        ];
        $etsyAuthData = $this->getEtsyAuthData([
            "company_application" => $companyApplication
        ]);
        
        $etsyAuthData = $this->updateEtsyAuthData($companyApplication, $etsyAuthData, [
            "client_id" => $dataToSend["client_id"],
            "code_challenge" => $dataToSend["code_challenge"],
            "verifier" => $verifier,
            "redirect_url" => $dataToSend['redirect_uri']
        ]);
        
        
        $authorizationUrl = $this->etsyClient->getAuthorizationUrl($dataToSend); 
        return $authorizationUrl;
    }

    public function checkConnection($companyApplication) {

        $etsyAuth =$this->entityManager->getRepository(EtsyAuth::class);
        $etsyAuthData =$etsyAuth->findOneBy(['company'=>$companyApplication->getCompany(),]);
        if(!is_null($etsyAuthData)||method_exists($etsyAuthData,'getAccessToken')){
            if((!empty($etsyAuthData->getAccessToken()))){
                 return$etsyAuthData;
            }
        }else{
            return;
        }
    }
    public function requestEtsyAccessToken($companyApplication, $params = [])
    {
        $etsyAuthData = $this->getEtsyAuthData([
            "company_application" => $companyApplication
        ]);
        
        $codeVerifier = (!empty($etsyAuthData)) ? $etsyAuthData->getVerifier() : "";

        $dataToSend = [
            "grant_type" => "authorization_code",
            "client_id"  => $params['keyString'] ? $params['keyString'] : "",
            "redirect_uri" => $params['redirectUri'] ? $params['redirectUri'] : "",
            "code" => isset($params['code']) ? $params['code'] : "",
            "code_verifier" => $codeVerifier
        ];
        
        list($accessTokenData, $error) = $this->etsyClient->requestAccessToken($dataToSend);
        $accessTokenData = json_decode($accessTokenData);

        $accessToken = property_exists($accessTokenData, "access_token") ? $accessTokenData->access_token : "";
        $etsyUserId = explode(".", $accessToken);
        $etsyUserId = isset($etsyUserId[0]) ? $etsyUserId[0] : "";

        if (!empty($accessTokenData)) {

            $etsyAuthData = $this->updateEtsyAuthData($companyApplication, $etsyAuthData, [
                "access_token" => $accessToken,
                "refresh_token" => property_exists($accessTokenData, "refresh_token") ? $accessTokenData->refresh_token : "",
                "raw_data" => json_encode($accessTokenData),
                "token_expires_in" => property_exists($accessTokenData, "expires_in") ? time() + $accessTokenData->expires_in : "",
                "etsy_user_id" => $etsyUserId
            ]);
        }

        return $etsyAuthData;
    }

    public function DisConnectApp($companyApplication) {
        
        $etsyAuth = $this->entityManager->getRepository(EtsyAuth::class);
        
        $authData = $etsyAuth->findOneBy(['company' => $companyApplication->getCompany(),]);
        $authData->setAccessToken(null);
        $this->entityManager->persist($authData);
        $this->entityManager->flush();
    }
    public function getEtsyShopByUserId($companyApplication, $params = [])
    {
        $dataToSend = [
            "client_id"  => $params['keyString'] ? $params['keyString'] : "",
            "userId" => isset($params['userId']) ? $params['userId'] : "",
            "code" => isset($params['accessToken']) ? $params['accessToken'] : "",
        ];
        
        list($userShopData, $error) = $this->etsyClient->getShopData($dataToSend);
        
        return json_decode($userShopData);
    }

    public function requestEtsyUserShopData($companyApplication, $params = [])
    {
        $dataToSend = [
            "client_id"  => $params['keyString'] ? $params['keyString'] : "",
            "code" => isset($params['accessToken']) ? $params['accessToken'] : "",
        ];

        list($userShopData, $error) = $this->etsyClient->requestUserShopData($dataToSend);

        return $userShopData;
    }

    public function getEtsyAuthData($params = [])
    {
        $etsyAuthRepo = $this->entityManager->getRepository(EtsyAuth::class);
        $etsyAuthData = $etsyAuthRepo->findOneBy($params); 

        return $etsyAuthData;
    }

    public function getEtsyShopData($params = [])
    {
        $etsyShopRepo = $this->entityManager->getRepository(EtsyShop::class);
        $etsyShopData = $etsyShopRepo->findOneBy($params); 

        return $etsyShopData;
    }

    public function getEtsyShopDatas($params = [])
    {
        $etsyShopRepo = $this->entityManager->getRepository(EtsyShop::class);
        $etsyShopData = $etsyShopRepo->findBy($params); 

        return $etsyShopData;
    }

    public function getEtsyShippingProfileDatas($params = [])
    {
        $etsyShippingProfileRepo = $this->entityManager->getRepository(ShippingProfiles::class);
        $etsyShippingProfileData = $etsyShippingProfileRepo->findBy($params); 

        return $etsyShippingProfileData;
    }

    public function getEtsyShippingProfileData($params = [])
    {
        $etsyShippingProfileRepo = $this->entityManager->getRepository(ShippingProfiles::class);
        $etsyShippingProfileData = $etsyShippingProfileRepo->findOneBy($params); 

        return $etsyShippingProfileData;
    }

    public function updateEtsyShopData($companyApplication, $etsyShopData, $params = [])
    {
        if (empty($etsyShopData)) {
            $etsyShopData = new EtsyShop;
        }

        if (!empty($companyApplication)) {
            $etsyShopData->setCompany($companyApplication->getCompany());
            $etsyShopData->setCompanyApplication($companyApplication);
        }
        
        if (isset($params['shopId'])) {
            $etsyShopData->setShopId($params['shopId']);
        }

        if (isset($params['userId'])) {
            $etsyShopData->setUserId($params['userId']);
        }

        if (isset($params['shopName'])) {
            $etsyShopData->setShopName($params['shopName']);
        }

        if (isset($params['currencyCode'])) {
            $etsyShopData->setCurrencyCode($params['currencyCode']);
        }

        if (isset($params['shopUrl'])) {
            $etsyShopData->setShopUrl($params['shopUrl']);
        }

        if (isset($params['status'])) {
            $etsyShopData->setStatus($params['status']);
        }

        if (isset($params['isDefault'])) {
            $etsyShopData->setIsDefault($params['isDefault']);
        }

        $etsyShopData->setUpdatedAt(time());

        $em = $this->entityManager;
        $em->persist($etsyShopData);
        $em->flush();
      
        return $etsyShopData;
    }

    public function updateEtsyShippingProfileData($companyApplication, $etsyShippingProfile, $params = [])
    {
        if (empty($etsyShippingProfile)) {
            $etsyShippingProfile = new ShippingProfiles;
        }

        if (!empty($companyApplication)) {
            $etsyShippingProfile->setCompany($companyApplication->getCompany());
            $etsyShippingProfile->setCompanyApplication($companyApplication);
        }

        if (isset($params['shopId'])) {
            $etsyShippingProfile->setShopId($params['shopId']);
        }

        if (isset($params['shippingProfileId'])) {
            $etsyShippingProfile->setShippingProfileId($params['shippingProfileId']);
        }

        if (isset($params['title'])) {
            $etsyShippingProfile->setTitle($params['title']);
        }

        if (isset($params['displayLabel'])) {
            $etsyShippingProfile->setDisplayLabel($params['displayLabel']);
        }

        if (isset($params['isDefault'])) {
            $etsyShippingProfile->setIsDefault($params['isDefault']);
        }

        $etsyShippingProfile->setUpdatedAt(time());

        $em = $this->entityManager;
        $em->persist($etsyShippingProfile);
        $em->flush();

        return $etsyShippingProfile;
    }

    public function updateEtsyAuthData($companyApplication, $etsyAuthData, $params = [])
    {
        if (empty($etsyAuthData)) {
            $etsyAuthData = new EtsyAuth;
        }

        if (!empty($companyApplication)) {
            $etsyAuthData->setCompany($companyApplication->getCompany());
            $etsyAuthData->setCompanyApplication($companyApplication);
        }
        
        if (isset($params['client_id'])) {
            $etsyAuthData->setClientId($params['client_id']);
        }

        if (isset($params['code_challenge'])) {
            $etsyAuthData->setCodeChallenge($params['code_challenge']);
        }

        if (isset($params['verifier'])) {
            $etsyAuthData->setVerifier($params['verifier']);
        }

        if (isset($params['access_token'])) {
            $etsyAuthData->setAccessToken($params['access_token']);
        }

        if (isset($params['refresh_token'])) {
            $etsyAuthData->setRefreshToken($params['refresh_token']);
        }

        if (isset($params['raw_data'])) {
            $etsyAuthData->setRawData($params['raw_data']);
        }

        if (isset($params['token_expires_in'])) {
            $etsyAuthData->setTokenExpiresIn($params['token_expires_in']);
        }

        if (isset($params['etsy_user_id'])) {
            $etsyAuthData->setEtsyUserId($params['etsy_user_id']);
        }

        if (isset($params['redirect_url'])) {
            $etsyAuthData->setRedirectUrl($params['redirect_url']);
        }
        
        $etsyAuthData->setUpdatedAt(time());

        $em = $this->entityManager;
        $em->persist($etsyAuthData);
        $em->flush();

        return $etsyAuthData;
    }

    public function get_platform_products($params = [], $companyApplication = null)
    {
        if ($this->wixClient->accessToken == null && $companyApplication != null) {
            $wixClient = new WixClient($companyApplication);
            return $wixClient->get_products($params);
        } else {
            return $this->wixClient->get_products($params);
        }
    }

    // public function importProductsOnEtsy($companyApplication, $params = [])
    // {
    //     $catalogHelper = $this->getAppHelper("catalog");
    //     $prodIds = isset($params['prodIds']) ? $params['prodIds'] : [];

    //     $wixProdIds = [];
    //     foreach($prodIds as $prodId) {
    //         $product = $catalogHelper->get_product(['id' => $prodId]);
    //         $wixProdIds[] = !empty($product) ? $product->getWixProdId() : "";
    //     }
    
    //     $apiParams = [
    //         'query' => [
    //             'filter' => json_encode([
    //                 'id' => [
    //                     '$hasSome' => $wixProdIds
    //                 ],
    //             ])
    //         ],
    //         'includeHiddenProducts' => true
    //     ];
        
    //     list($wixProducts, $error) = $this->get_platform_products($apiParams);
    //     $wixProductsObj = json_decode($wixProducts);
    //     $wixProducts = property_exists($wixProductsObj, "products") ? $wixProductsObj->products : [];

    //     $etsyDefaultShop = $this->getEtsyShopData([
    //         "company_application" => $companyApplication
    //     ]);

    //     $etsyAuthData = $this->reRequestAccessToken($companyApplication);

    //     $etsyDefaultShopId = !empty($etsyDefaultShop) ? $etsyDefaultShop->getShopId() : "";

    //     $dataToSend = [
    //         "shopId" => $etsyDefaultShopId,
    //         "AccessToken" => !empty($etsyAuthData) ? $etsyAuthData->getAccessToken() : "",
    //         "client_id" => !empty($etsyAuthData) ? $etsyAuthData->getClientId() : "",
    //     ];

    //     $etsyProductResponse = [];
    //     foreach($wixProducts as $wixProduct) {
            
    //         $dataToSend['wixProduct'] = $wixProduct;
    //         $etsyProductResponse[] = $this->addProductsOnEtsy($companyApplication, $dataToSend);
    //     }

    //     return $etsyProductResponse;
    // }

    public function importProductsOnEtsy($companyApplication, $request, $params = [])
    {   
        $catalogHelper = $this->getAppHelper("catalog");
        $prodIds = isset($params['prodIds']) ? $params['prodIds'] : [];
        
        $wixProdIds = [];
        foreach($prodIds as $prodId) {
            $product = $catalogHelper->get_product(['id' => $prodId]);
            $wixProdIds[] = !empty($product) ? $product->getWixProdId() : "";
        }
        $params = $request->request->all();

        $result = ['added' => 0, 'skipped' => 0];

        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (empty($params['limit'])) {
            $params['limit'] = 100;
        }

        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0

        $batch = (int) $params['limit'] + (int) $start;
        if ($batch > count($prodIds)) {
            $batch = count($prodIds);
        }
        
        if ($batch <= count($prodIds)) {
            
            $toProcessProducts = array_slice($wixProdIds, $start, $params['limit']);

            $apiParams = [
                'query' => [
                    'filter' => json_encode([
                        'id' => [
                            '$hasSome' => $toProcessProducts
                        ],
                    ])
                ],
                'includeHiddenProducts' => true,
                'includeVariants' => true
            ];
            
            list($wixProducts, $error) = $this->get_platform_products($apiParams, $companyApplication);
            $wixProductsObj = json_decode($wixProducts);
            $wixProducts = property_exists($wixProductsObj, "products") ? $wixProductsObj->products : [];
    
            $etsyDefaultShop = $this->getEtsyShopData([
                "company_application" => $companyApplication
            ]);
            
            $etsyAuthData = $this->reRequestAccessToken($companyApplication);
        
            $etsyDefaultShopId = !empty($etsyDefaultShop) ? $etsyDefaultShop->getShopId() : "";
    
            $dataToSend = [
                "shopId" => $etsyDefaultShopId,
                "AccessToken" => !empty($etsyAuthData) ? $etsyAuthData->getAccessToken() : "",
                "client_id" => !empty($etsyAuthData) ? $etsyAuthData->getClientId() : "",
            ];
        
            $etsyProductResponse = [];
            foreach($wixProducts as $wixProduct) {

                //clients with removed tags & spaces in description
                $clients_hashes_esc = ['VerreUnique54d5','DanBrianneDesigns6677'];
                if (in_array($companyApplication->getCompany()->getStoreHash(), $clients_hashes_esc)) {
                    if(isset($wixProduct->description) && !empty($wixProduct->description)) {
                        $wixProduct->description = str_replace("&nbsp;", " ", strip_tags($wixProduct->description));
                    }
                }

                $dataToSend['wixProduct'] = $wixProduct;
                
                list($etsyProduct, $error) = $this->addProductsOnEtsy($companyApplication, $request, $dataToSend);
                
                if (!empty($error)) {

                    ++$result['skipped'];

                    $notifications[] = array(
                        'type' => 'danger',
                        'message' => $error . " (Product : ". $wixProduct->name . " )",
                    );

                } else {
                    ++$result['added'];
                    $notifications[] = array(
                        'type' => 'success',
                        'message' =>  " (Product : ". $wixProduct->name . " Synced Successfully )",
                    );
                }
            }
        }
        if (empty($notifications)) {
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
        }
        
        return array(
            'totalCount' => count($wixProdIds),
            'items' => $toProcessProducts,
            'notifications' => $notifications,
        );
    }

    public function reRequestAccessToken($companyApplication, $params = [])
    {
        $etsyAuthData = $this->getEtsyAuthData([
            "company_application" => $companyApplication
        ]);
        
        if (!empty($etsyAuthData) && $etsyAuthData->getTokenExpiresIn() < time()) {
            
            $dataToSend = [
                "grant_type" => "refresh_token",
                "client_id"  => $etsyAuthData->getClientId(),
                "refresh_token" => $etsyAuthData->getRefreshToken(),
            ];
            
            list($accessTokenData, $error) = $this->etsyClient->requestAccessToken($dataToSend);
            $accessTokenData = json_decode($accessTokenData);
           
            if (!empty($accessTokenData)) {

                $accessToken = property_exists($accessTokenData, "access_token") ? $accessTokenData->access_token : ""; 
                $etsyUserId = explode(".", $accessToken); 
                $etsyUserId = isset($etsyUserId[0]) ? $etsyUserId[0] : "";

                $etsyAuthData = $this->updateEtsyAuthData($companyApplication, $etsyAuthData, [
                    "access_token" => $accessToken,
                    "refresh_token" => property_exists($accessTokenData, "refresh_token") ? $accessTokenData->refresh_token : "",
                    "raw_data" => json_encode($accessTokenData),
                    "token_expires_in" => property_exists($accessTokenData, "expires_in") ? time() + $accessTokenData->expires_in : "",
                    "etsy_user_id" => $etsyUserId
                ]);

                if (empty($accessToken)) {
                    $notifications = [
                        'type' => 'warning',
                        'message' => "Connect shop again.",
                    ];
                    $redirectUrl = $this->generateUrl('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
                    $this->session->getFlashBag()->add($notifications['type'], $notifications['message']);
                    return header('Location: '.$redirectUrl);
                }

                return $etsyAuthData;
            }

        } else {

            return $etsyAuthData;
        }
    }

    public function addProductsOnEtsy($companyApplication, $request, $params = [])
    {  
        $errors = "";

        $catalogHelper = $this->getAppHelper("catalog");

        $wixProduct = isset($params['wixProduct']) ? $params['wixProduct'] : []; 
 
        $wixProductId = property_exists($wixProduct, "id") ? $wixProduct->id : "";  
 
        $wixProductVariantsData = property_exists($wixProduct, "variants") ? $wixProduct->variants : []; 
        $wixManageVariants = property_exists($wixProduct, "manageVariants") ? $wixProduct->manageVariants : false;
      
        //list($wixProductVariantsData, $error) = $this->wixClient->getProductVariants($wixProductId);
        //$wixProductVariantsData = json_decode($wixProductVariantsData);

        $wixProductImages = property_exists($wixProduct, "media") ? ( property_exists($wixProduct->media, "items") ? $wixProduct->media->items : [] ) : [];
        
        $wixProductOptions = property_exists($wixProduct, "productOptions") ? $wixProduct->productOptions : [];

        $productPrice = property_exists($wixProduct, "priceData") ? (property_exists($wixProduct->priceData, "price") ? $wixProduct->priceData->price : "") : "";
        $productCreatedDate = property_exists($wixProduct, "createdDate") ? $wixProduct->createdDate : "";
        $productCreatedYear = date('Y', strtotime($productCreatedDate));
        
        $productStock = 0;
        if (property_exists($wixProduct, "stock") && property_exists($wixProduct->stock, "trackInventory") && $wixProduct->stock->trackInventory == true) {
            $productStock = property_exists($wixProduct->stock, "quantity") ? $wixProduct->stock->quantity : 0;
        }
      
        if(isset($productPrice) && $productPrice > 5000000) {
            $this->container->get('translator')->trans("price_cannot_be_more");
        }
        // list($shopShippingProfileData, $error) = $this->etsyClient->getShopShippingProfiles([
        //     'client_id' => $params['client_id'],
        //     'shopId'=> $params['shopId'],
        //     "code" => $params['AccessToken']
        // ]);
            
        //list($taxonomyData, $error) = $this->etsyClient->getSellerTaxonomyNodes(['client_id' => $params['client_id']]);

        $wixProductCollections = property_exists($wixProduct, "collectionIds") ? $wixProduct->collectionIds : []; 
        $mappedCategory = $this->getMappedCategory($companyApplication, $wixProductCollections);
        
        $defaultShippingProfile = $this->getSettingValue([
            "company_application" => $companyApplication,
            "setting_name" => "shipping_profile"
        ]);

        $defaultShippingProfileId = !empty($defaultShippingProfile) ? $defaultShippingProfile->getSettingValue() : "";
        
        $productWeight = 0.0;
        if (property_exists($wixProduct, "weight")) {
            $productWeight = (float) $wixProduct->weight;
        } elseif(property_exists($wixProduct, "weightRange")) {
            $productWeight = property_exists($wixProduct->weightRange, "maxValue") ? (float) $wixProduct->weightRange->maxValue : 0.0;
        }
   
        $productData = [
            "title" => property_exists($wixProduct, "name") ? $wixProduct->name : "",
            "description" => property_exists($wixProduct, "description") ?  preg_replace('/<[^>]*>|&nbsp;|\t/', '', $wixProduct->description) : "",
            "price" => $productPrice,
            "who_made" => "i_did",
            "when_made" => "made_to_order",//$productCreatedYear."s",
            "taxonomy_id" => (int)$mappedCategory,// etsy category Id static for temporary
            "item_weight" => $productWeight, # Default weight is 0.0 but It can't be accept by Etsy.
            "type" => property_exists($wixProduct, "productType") ? $wixProduct->productType : "",
            "quantity" => $productStock,
            "sku" => property_exists($wixProduct, "variants") ? $wixProduct->variants[0]->variant->sku : ""
        ];
       
        if (property_exists($wixProduct, "productType") && $wixProduct->productType == "physical") {
            $productData['shipping_profile_id'] = $defaultShippingProfileId; //"196420205948"; // static for temporary
        }

        $product = $catalogHelper->get_product([
            "wix_prod_id" => $wixProduct->id,
            "company_application" => $companyApplication
        ]);
      
        # Error Handling on Some basic Product Requirements             
        if(isset($productData['sku']) && mb_strlen($productData['sku'],'UTF-8') > 32) {
            $product->setSyncStatus(2);
            $product->setSyncMessage($this->container->get('translator')->trans("sku_length_cannot"));
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            return [$product, $this->container->get('translator')->trans("sku_length_cannot")];
        }

        if (isset($productData['item_weight']) && $productData['item_weight'] == 0) {
            $product->setSyncStatus(2); 
            $product->setSyncMessage($this->container->get('translator')->trans("weight_can_not_be_empty"));
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            return [$product, $this->container->get('translator')->trans("weight_can_not_be_empty")];
        }
      
        if ( /*$product->getSyncStatus() == 1 && */ !empty($product->getEtsyListingId())) {
           
            if(isset($productData["price"])  && $productData['price'] > 50000.00) {
                return [$product, $this->container->get('translator')->trans("price_cannot_be_more")];
            } else {
                $productData['is_supply'] = TRUE;
                $params['listingId'] = $product->getEtsyListingId(); 
                list($etsyProductListing, $error) = $this->etsyClient->updateDraftListing($productData, $params); 
                $etsyProductListing = json_decode($etsyProductListing); 

            }
            // update Product on etsy
            // update Product Images on Etsy
            $imageErrors = $this->updateEtsyProductImage($companyApplication, $params, $request, $wixProductImages);
            $productImageMappingRepo = $this->entityManager->getRepository(WixEtsyProductImageMapping::class);
            $products =  $productImageMappingRepo->findBy(['listing_id' => $product->getEtsyListingId() ]);
            // dd($products,$params);
            // update Product Images on Etsy
            foreach($wixProductImages as $wixprimages) {
                $wix_c_img[] = $wixprimages->id;
                
            }
            foreach($products as $ids) {  
                if(!in_array($ids->getWixImageId(),$wix_c_img)) { 
                    $params ['imageId'] = $ids->getEtsyImageId();
                    $delete_response = $this->ImageDelete($params);
                } 
            }
            // update Product Variants on Etsy
            //$etsyListingProperties = $this->etsyClient->getListingProperties($params); dd($etsyListingProperties);

            $productVariantsData = $this->arrangeProductVariants($companyApplication, $wixProductVariantsData, $wixManageVariants);

            //list($etsyProductVariantsData, $error) = $this->etsyClient->getListingInventory($params);

            if (!empty($productVariantsData)) {
                
                list($etsyProductListingInventory, $error) = $this->etsyClient->updateListingInventory($productVariantsData, $params);
                $etsyProductListingInventory = json_decode($etsyProductListingInventory);

                if (!is_array($etsyProductListingInventory)) {

                    $errors = property_exists($etsyProductListingInventory, "error") ? $etsyProductListingInventory->error : "";
                }
            } else {
                
                $stock = property_exists($wixProduct, "stock") ? $wixProduct->stock : (object)[];
                $quantity = property_exists($stock, "quantity") ? $stock->quantity : 0; 

                $priceData = property_exists($wixProduct, "price") ? $wixProduct->price : (object)[];
                $price = property_exists($priceData, "price") ? $priceData->price : 0;

                $variantProductData['products'][] = [
                    "offerings" => [
                        [
                            "price" => (string) $price,
                            "is_enabled" => property_exists($wixProduct, "visible") ? $wixProduct->visible : false,
                            "quantity" => $quantity,
                        ]
                    ]
                ];
                
                list($etsyProductListingInventory, $error) = $this->etsyClient->updateListingInventory($variantProductData, $params);
            }

        } else {
           
            // create Product on etsy
            list($etsyProductListing, $error) = $this->etsyClient->createDraftListing($productData, $params); 
            $etsyProductListing = json_decode($etsyProductListing); 
        
            if (!is_array($etsyProductListing)) {
                $errors = property_exists($etsyProductListing, "error") ? $etsyProductListing->error : "";
                $params["listingId"] = property_exists($etsyProductListing, "listing_id") ? $etsyProductListing->listing_id : "";
            }
            
            if (!empty($params["listingId"])) {

                // update Product Images on Etsy
                $imageErrors = $this->updateEtsyProductImage($companyApplication, $params, $request, $wixProductImages);
                $productImageMappingRepo = $this->entityManager->getRepository(WixEtsyProductImageMapping::class);
                $products =  $productImageMappingRepo->findBy(['listing_id' => $product->getEtsyListingId() ]);
                // dd($products,$params);
                // update Product Images on Etsy
                foreach($wixProductImages as $wixprimages) {
                    $wix_c_img[] = $wixprimages->id;
                }
                foreach($products as $ids) {  
                    if(!in_array($ids->getWixImageId(),$wix_c_img)) {  
                        $params ['imageId'] = $ids->getEtsyImageId();
                        $delete_response = $this->ImageDelete($params);
                    } 
                }

                $productVariantsData = $this->arrangeProductVariants($companyApplication, $wixProductVariantsData, $wixManageVariants);
                
                if (!empty($productVariantsData)) {

                    list($etsyProductListingInventory, $error) = $this->etsyClient->updateListingInventory($productVariantsData, $params);
                    $etsyProductListingInventory = json_decode($etsyProductListingInventory);

                    if (!is_array($etsyProductListingInventory)) {

                        $errors = property_exists($etsyProductListingInventory, "error") ? $etsyProductListingInventory->error : "";
                    }
                }
            }
        }
        
        if (!empty($etsyProductListing)) {

            $etsyListingError = "";
            if (is_array($etsyProductListing) && isset($etsyProductListing[0])) {
                
                if (property_exists($etsyProductListing[0], "path") && property_exists($etsyProductListing[0], "message")) {
                    $etsyListingError = $etsyProductListing[0]->path. " ". $etsyProductListing[0]->message;

                    $errors = $etsyListingError;

                    //$product = $catalogHelper->get_product(["wix_prod_id" => $wixProduct->id]);

                    //$product->setSyncStatus(2); // 2 For Not Imported on Etsy
                    //$product->setSyncMessage($etsyListingError);
    
                    //$this->entityManager->persist($product);
                    //$this->entityManager->flush();
                }

                //return [ $product, $etsyListingError];

            } elseif (property_exists($etsyProductListing, "listing_id") && property_exists($etsyProductListing, "created_timestamp")) {

                //$product = $catalogHelper->get_product(["wix_prod_id" => $wixProduct->id]);

                $product->setEtsyListingId($etsyProductListing->listing_id);
                $product->setCreatedOnEtsy($etsyProductListing->created_timestamp);
                $product->setSyncStatus(1); // 1 For Successfully Imported on Etsy
    
                $this->entityManager->persist($product);
                $this->entityManager->flush();

                //return [ $product, ""];
            }
           
        } 
        
        if (isset($errors) && !empty($errors)) {

            $product->setSyncStatus(2); // 2 For Not Imported on Etsy
            $product->setSyncMessage(ucfirst(trim($errors,"/")));
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return [ $product, ucfirst(trim($errors,"/"))];

        } else {

            $product->setSyncStatus(1); // 1 For Not Imported on Etsy
            $product->setSyncMessage("");
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return [ $product, ""];
        }

    }

    public function getEtsyWixProductImageMapping($params = [])
    {
        $productImageMappingRepo = $this->entityManager->getRepository(WixEtsyProductImageMapping::class);
        $productImageMappingData = $productImageMappingRepo->findOneBy($params); 

        return $productImageMappingData;
    }
    public function ImageDelete($params = []) {
        list($response,$error) = $this->etsyClient->deleteListingImage($params);
        $productImageMappingRepo = $this->entityManager->getRepository(WixEtsyProductImageMapping::class);
        $product = $productImageMappingRepo->findOneBy(['etsy_image_id' => $params['imageId']]);
        $productImageMappingRepo->remove($product);
        $this->entityManager->flush();
 
        return $response;
     }
     public function updateEtsyProductImage($companyApplication, $params, $request, $wixProductImages = [])
     {
         $errors = [];
         foreach ($wixProductImages as $productImage) {
             
             $productImageMappingData = $this->getEtsyWixProductImageMapping([
                 "listing_id" => isset($params['listingId']) ? $params['listingId'] : "",
                 "wix_prod_id" => (isset($params['wixProduct']) && !empty($params['wixProduct'])) ? $params['wixProduct']->id : "",
                 "wix_image_id" => property_exists($productImage, "id") ? $productImage->id : ""
             ]);
             
             $productImageMappingData = !empty($productImageMappingData) ? $productImageMappingData : new WixEtsyProductImageMapping;
             if ($productImageMappingData->getCompanyApplication() != null) {
                continue;
             } 
             $productImageMappingData->setCompanyApplication($companyApplication);
             $productImageMappingData->setCompany($companyApplication->getCompany());
             $productImageMappingData->setListingId(isset($params['listingId']) ? $params['listingId'] : "");
             $productImageMappingData->setWixProdId((isset($params['wixProduct']) && !empty($params['wixProduct'])) ? $params['wixProduct']->id : "");
             $productImageMappingData->setWixImageId(property_exists($productImage, "id") ? $productImage->id : "");
             
             $image = property_exists($productImage, "image") ? $productImage->image : (object)[];
             $imageUrl = property_exists($image, "url") ? $image->url : "";
 
             if (!empty($imageUrl)) {
 
                 $serverData = $request->server->all();
                 $documentRoot = isset($serverData['DOCUMENT_ROOT']) ? $serverData['DOCUMENT_ROOT'] : "";
                 $filePath = $documentRoot.'/resource/images/'.$productImage->id;
 
                 $imageContent = file_get_contents($imageUrl);
                 file_put_contents($filePath, $imageContent);
                 
                 $imageData = [
                     "imagePath" => $filePath
                 ];
                 
                 list($imageResponse, $error) = $this->etsyClient->uploadListingImage($imageData, $params); 
                 $imageResponse = json_decode($imageResponse);
 
                 unlink($filePath);
 
                 if (is_object($imageResponse)) {
 
                     $listingImageId = property_exists($imageResponse, "listing_image_id") ? $imageResponse->listing_image_id : "";
 
                     $error = property_exists($imageResponse, "error") ? $imageResponse->error : "";
 
                     if (!empty($error)) {
                         $errors[] = $error;
                     }
 
                     if (!empty($listingImageId)) {
                         $productImageMappingData->setEtsyImageId($listingImageId);
 
                         $this->entityManager->persist($productImageMappingData);
                         $this->entityManager->flush();
                     }
                 }
             }
         }
 
         return $errors;
     }

    public function arrangeProductVariants($companyApplication, $wixProductVariants = [], $wixManageVariants = false)
    {
        //$wixProductVariants = property_exists($wixProductVariantsData, "variants") ? $wixProductVariantsData->variants : [];

        $variantProduct = []; //dd($wixProductVariants);

        foreach ($wixProductVariants as $variant) { //dd($variant);

            $variantChoices = property_exists($variant, "choices") ? $variant->choices : (object)[];

            $variantPrice = property_exists($variant, "variant") ? 
                                    ( property_exists($variant->variant, "priceData") ? 
                                        ( property_exists($variant->variant->priceData, "price") ? $variant->variant->priceData->price : "" ) 
                                    : "" ) 
                                : "";
            $isVisible = property_exists($variant, "variant") ? ( property_exists($variant->variant, "visible") ? $variant->variant->visible : "" ) : "";

            $quantity = property_exists($variant, "stock") ? ( property_exists($variant->stock, "quantity") ? $variant->stock->quantity : 1 ) : 1;

            $sku = property_exists($variant, "variant") ? ( property_exists($variant->variant, "sku") ? $variant->variant->sku : "" ) : "";
            
            $propertyValue = [];
            $offerings = [];

            $propertyIds = ["513","514"];
            $updatedPropertyIds = [];
            $i = 0;
            
            foreach ($variantChoices as $variantName => $variantValue ) {

                $propertyValue[] = [
                    "property_id" => isset($propertyIds[$i]) ? $propertyIds[$i] : 513,
                    "value_ids" => [1],
                    "property_name" => $variantName,
                    "values" => [$variantValue]
                ];

                $offerings[] = [
                    "price" => (string) $variantPrice,
                    "is_enabled" => $isVisible,
                    "quantity" => $quantity,
                ];

                $updatedPropertyIds[] = isset($propertyIds[$i]) ? $propertyIds[$i] : 513;

                $i++;
            }
            
            if (!empty($offerings) && !empty($propertyValue)) {

                $variantProduct['products'][] = [
                    "offerings" => $offerings,
                    "property_values" => $propertyValue,
                    "sku" => $sku
                ];

                if ($wixManageVariants) {
                    
                    $variantProduct["price_on_property"] = $updatedPropertyIds;
                    $variantProduct["quantity_on_property"] = $updatedPropertyIds;
                    $variantProduct['sku_on_property'] = $updatedPropertyIds;
                }
            }
        }

        return $variantProduct;
        

        
        // $etsyVariantsData = [
        //     'products' => [
        //         [
        //             'offerings' => [
        //                 [
        //                     'price' => '9.99',
        //                     'quantity' => 2,
        //                     'is_enabled' => TRUE
        //                 ],
        //                 [
        //                     'price' => '50',
        //                     'quantity' => 1,
        //                     'is_enabled' => TRUE
        //                 ]
        //             ],
        //             'property_values' => [
        //                 [
        //                     'property_id' => 513,
        //                     'value_ids' => [1],
        //                     "property_name" => "size",
        //                     "values" => ["L"]
        //                 ],
        //                 [
        //                     'property_id' => 514,
        //                     'value_ids' => [1],
        //                     "property_name" => "color",
        //                     "values" => ["Red"]
        //                 ],
        //             ]
        //         ],
        //         [
        //             'offerings' => [
        //                 [
        //                     'price' => '9.99',
        //                     'quantity' => 2,
        //                     'is_enabled' => TRUE
        //                 ],
        //                 [
        //                     'price' => '50',
        //                     'quantity' => 1,
        //                     'is_enabled' => TRUE
        //                 ]
        //             ],
        //             'property_values' => [
        //                 [
        //                     'property_id' => 513,
        //                     'value_ids' => [1],
        //                     "property_name" => "size",
        //                     "values" => ["L"]
        //                 ],
        //                 [
        //                     'property_id' => 514,
        //                     'value_ids' => [1],
        //                     "property_name" => "color",
        //                     "values" => ["Green"]
        //                 ],
        //             ]
        //         ],
        //     ],
        //     "price_on_property" => [513,514],
        //     "quantity_on_property" => [513,514],
        //     "sku_on_property" => [513,514]
        // ];
        
        // return $etsyVariantsData;
    }

    public function getEtsyShippingProfile($companyApplication, $params = [])
    {
        list($shopShippingProfileData, $error) = $this->etsyClient->getShopShippingProfiles([
            'client_id' => $params['client_id'],
            'shopId'=> $params['shopId'],
            "code" => $params['AccessToken']
        ]);

        return [$shopShippingProfileData, $error];
    }

    /**
     * This Function is not related to the app only used for get Product data from the etsy end (which is not an app functionality), only for once.
    */
    public function getProductsFromEtsy($companyApplication, $params = [])
    {
        $etsyAuth = $this->reRequestAccessToken($companyApplication,[]);

        if (!empty($etsyAuth)) {

            // $defaultShop = $this->getEtsyShopData([
            //     "company_application" => $companyApplication,
            //     "is_default" => 1
            // ]);

            // $defaultShopId = !empty($defaultShop) ? $defaultShop->getShopId() : "";

            $settingData = $this->getSettingValue([
                "company_application" => $companyApplication,
                "setting_name" => "shop"
            ]);

            $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
            
            list($shopListings, $error) = $this->etsyClient->getShopListings([
                'client_id' => $etsyAuth->getClientId(),
                'shopId'=> $defaultShopId,
                "code" => $etsyAuth->getAccessToken()
            ]);
        }
        
        $shopListings = json_decode($shopListings);

        if (!empty($shopListings) && property_exists($shopListings, "count") && $shopListings->count > 0) {
            if (property_exists($shopListings, "results") && is_array($shopListings->results)) {
                foreach($shopListings->results as $etsyListing) {
                    
                    $product = new Products();
                    $product->setCompany($companyApplication->getCompany());
                    $product->setCompanyApplication($companyApplication);
                    $product->setUpdatedAt(time());
                    $product->setName($etsyListing->title);
                    $product->setPrice($etsyListing->price->amount);
                    $product->setEtsyListingId($etsyListing->listing_id);

                    $this->entityManager->persist($product);
                    $this->entityManager->flush();
                }
            }
        }
        
        return [$shopListings, $error];
    }

    public function getOrdersFromEtsy($companyApplication, $params = [])
    {
        $etsyAuth = $this->reRequestAccessToken($companyApplication,[]);

        $shopReceipts = json_encode([]);

        if (!empty($etsyAuth)) {

            // $defaultShop = $this->getEtsyShopData([
            //     "company_application" => $companyApplication,
            //     "is_default" => 1
            // ]);

            // $defaultShopId = !empty($defaultShop) ? $defaultShop->getShopId() : "";

            $settingData = $this->getSettingValue([
                "company_application" => $companyApplication,
                "setting_name" => "shop"
            ]);

            $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
            
            list($shopReceipts, $error) = $this->etsyClient->getShopReceipts([
                'client_id' => $etsyAuth->getClientId(),
                'shopId'=> $defaultShopId,
                "code" => $etsyAuth->getAccessToken()
            ]);
        }
        
        return json_decode($shopReceipts);
    }

    public function get_orders($params = [])
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);
      
        $company = isset($params['company']) ? $params['company'] : $this->container->get('app.runtime')->get_company_application()->getCompany();
        
        $orderRepo = $this->entityManager->getRepository(Orders::class); 
        $orders = $orderRepo->getOrders($params, $company);

        return [$orders, $params];
    }

    public function sync_order($request, CompanyApplication $companyApplication, $extraParams = [])
    {
        $catalogHelper = $this->getAppHelper("catalog");
        $is_success = false;
        $notifications = [];
        $params = $request->request->all();
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (empty($params['limit'])) {
            $params['limit'] = 100;
        }
        
        $fromDate = isset($params['fromDate']) ? strtotime($params['fromDate']) : "";
        $toDate = isset($params['toDate']) ? strtotime($params['toDate']) : "";

        $queryParams = [];
        if (isset($params['orderIds']) && !empty($params['orderIds'])) {

            $extraParams['orderIds'] = explode(",", $params['orderIds']);

        } else {

            if (isset($params['fromDate']) && !empty($params['fromDate'])) {
                $queryParams['min_created'] = (int)$params['fromDate'];
            }

            if (isset($params['toDate']) && !empty($params['toDate'])) {
                $queryParams['max_created'] = (int)$params['toDate'];
            } 

            if (isset($params['shipmentStatus']) && ($params['shipmentStatus'] == "true" || $params['shipmentStatus'] == "false")) {
                $queryParams['was_shipped'] = $params['shipmentStatus']; 
            } 

            if (isset($params['paymentStatus']) && ($params['paymentStatus'] == "true" || $params['paymentStatus'] == "false")) {
                $queryParams['was_delivered'] = $params['paymentStatus'];
            } 
        }
        
        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0
        $company = $companyApplication->getCompany();

        $result = ['added' => 0, 'skipped' => 0];

        $etsyAuth = $this->reRequestAccessToken($companyApplication,[]);

        if (!empty($etsyAuth)) {

            // $defaultShop = $this->getEtsyShopData([
            //     "company_application" => $companyApplication,
            //     "is_default" => 1
            // ]);

            $settingData = $this->getSettingValue([
                "company_application" => $companyApplication,
                "setting_name" => "shop"
            ]);

            $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
            
            $shopReceipts = (object) [];

            if (empty($extraParams)) {

                list($shopReceipts, $error) = $this->etsyClient->getShopReceipts([
                    'client_id' => $etsyAuth->getClientId(),
                    'shopId'=> $defaultShopId,
                    "code" => $etsyAuth->getAccessToken(),
                    "queryParams" => http_build_query($queryParams)
                ]);

                $shopReceipts = json_decode($shopReceipts);

            } elseif(isset($extraParams['orderIds'])) {

                $shopOrders = [];
                foreach ($extraParams['orderIds'] as $orderId) {
                    
                    list($shopReceipts, $error) = $this->etsyClient->getShopReceipt([
                        'client_id' => $etsyAuth->getClientId(),
                        'shopId'=> $defaultShopId,
                        "code" => $etsyAuth->getAccessToken(),
                        "receiptId" => $orderId
                    ]);
    
                    $shopOrders[] = json_decode($shopReceipts);
                }
                $shopReceipts = (object) [];
                $shopReceipts->count = count($shopOrders);
                $shopReceipts->results = $shopOrders;
            }
            
            if (is_object($shopReceipts) && property_exists($shopReceipts, "count") && $shopReceipts->count > 0) {
                if (property_exists($shopReceipts, "results") && !empty($shopReceipts->results)) {

                    foreach($shopReceipts->results as $etsyReceipt) {
                        
                        $orderProducts = property_exists($etsyReceipt, "transactions") ? $etsyReceipt->transactions : [];
                        $existedProducts = [];
                        $variantsData = [];

                        $orderCalculationData = [
                            "subTotal" => 0,
                            "totalShipping" => 0,
                            "totalQty" => 0,
                            "existedQty" => 0,
                            "totalTax" => 0,
                            "totalDiscount" => 0,
                        ];
                        
                        foreach ($orderProducts as $orderProduct) {
                            $isExistOrderProduct = $catalogHelper->get_product([
                                "etsy_listing_id" => property_exists($orderProduct, "listing_id") ? $orderProduct->listing_id : ""
                            ]);
                            
                            $orderProductQty = property_exists($orderProduct, "quantity") ? $orderProduct->quantity : 0;

                            $orderCalculationData['totalQty'] = $orderCalculationData['totalQty'] + $orderProductQty;

                            if (!empty($isExistOrderProduct)) {
                                
                                //$existedProducts[] = $orderProduct->listing_id;
                               
                                //$existedProducts[] = $isExistOrderProduct->getWixProdId(); # commented on 21 june
                                $existedProducts[] = $orderProduct;
                                // Order Calculation
                                
                                $orderProductQty = property_exists($orderProduct, "quantity") ? $orderProduct->quantity : 0;

                                $orderCalculationData['existedQty'] = $orderCalculationData['existedQty'] + $orderProductQty;
                                
                                if (property_exists($orderProduct, "price") && property_exists($orderProduct->price, "amount")) {
                                    $orderCalculationData['subTotal'] = $orderCalculationData['subTotal'] + (($orderProduct->price->amount/$orderProduct->price->divisor) * $orderProductQty);
                                }

                                if (property_exists($orderProduct, "shipping_cost") && property_exists($orderProduct->shipping_cost, "amount")) {
                                    $orderCalculationData['totalShipping'] = $orderCalculationData['totalShipping'] + ($orderProduct->shipping_cost->amount/$orderProduct->shipping_cost->divisor);
                                }

                            }
                        }
                        
                        if (!empty($existedProducts)) {

                            $etsyOrder = $this->getEtsyOrder([
                                "company_application" => $companyApplication,
                                "shop_id" => $defaultShopId,
                                "receipt_id" => property_exists($etsyReceipt, "receipt_id") ? $etsyReceipt->receipt_id : ""
                            ]);
    
                            if (empty($etsyOrder)) {
                                
                                $etsyOrder = new Orders;
                                $etsyOrder->setSyncStatus(2); # 2 For Not Synced on Wix.
                            }
    
                            $etsyOrder->setCompany($companyApplication->getCompany());
                            $etsyOrder->setCompanyApplication($companyApplication);
                            $etsyOrder->setShopId($defaultShopId);
                            $etsyOrder->setReceiptId(property_exists($etsyReceipt, "receipt_id") ? $etsyReceipt->receipt_id : "");
                            $etsyOrder->setBuyerEmail(property_exists($etsyReceipt, "buyer_email") ? $etsyReceipt->buyer_email : "");
                            $etsyOrder->setName(property_exists($etsyReceipt, "name") ? $etsyReceipt->name : "");
                            $etsyOrder->setFirstLine(property_exists($etsyReceipt, "first_line") ? $etsyReceipt->first_line : "");
                            $etsyOrder->setSecondLine(property_exists($etsyReceipt, "second_line") ? $etsyReceipt->second_line : "");
                            $etsyOrder->setCity(property_exists($etsyReceipt, "city") ? $etsyReceipt->city : "");
                            $etsyOrder->setState(property_exists($etsyReceipt, "state") ? $etsyReceipt->state : "");
                            $etsyOrder->setZip(property_exists($etsyReceipt, "zip") ? $etsyReceipt->zip : "");
                            $etsyOrder->setOrderStatus(property_exists($etsyReceipt, "status") ? $etsyReceipt->status : "");
                            $etsyOrder->setFormattedAddress(property_exists($etsyReceipt, "formatted_address") ? $etsyReceipt->formatted_address : "");
                            $etsyOrder->setCountryIso(property_exists($etsyReceipt, "country_iso") ? $etsyReceipt->country_iso : "");
                            $etsyOrder->setPaymentMethod(property_exists($etsyReceipt, "payment_method") ? $etsyReceipt->payment_method : "");
                            $etsyOrder->setIsShipped(property_exists($etsyReceipt, "is_shipped") ? $etsyReceipt->is_shipped : "");
                            $etsyOrder->setIsPaid(property_exists($etsyReceipt, "is_paid") ? $etsyReceipt->is_paid : "");
                            $etsyOrder->setCreatedAt(property_exists($etsyReceipt, "created_timestamp") ? $etsyReceipt->created_timestamp : "");
                            $etsyOrder->setRawData(property_exists($etsyReceipt, "transactions") ? json_encode($etsyReceipt->transactions) : "");
                            
                            $this->entityManager->persist($etsyOrder);

                            if (empty($orderCalculationData['totalShipping'])) {

                                if (property_exists($etsyReceipt, "total_shipping_cost") && property_exists($etsyReceipt->total_shipping_cost, "amount")) {
                                    
                                    $orderTotalShippingCost = $etsyReceipt->total_shipping_cost->amount/$etsyReceipt->total_shipping_cost->divisor;

                                    if ($orderTotalShippingCost != 0) {
                                        $perProductShippingCost = $orderTotalShippingCost / $orderCalculationData['totalQty'];
                                        $orderCalculationData['totalShipping'] = $orderCalculationData['existedQty'] * $perProductShippingCost;
                                    } else {
                                        $orderCalculationData['totalShipping'] = 0;
                                    }
                                }
                            }

                            if (property_exists($etsyReceipt, "total_tax_cost") && property_exists($etsyReceipt->total_tax_cost, "amount")) {
                                
                                $orderTotalTax = $etsyReceipt->total_tax_cost->amount/$etsyReceipt->total_tax_cost->divisor;

                                if ($orderTotalTax != 0) {
                                    $perProductTaxCost = $orderTotalTax / $orderCalculationData['totalQty'];
                                    $orderCalculationData['totalTax'] = $orderCalculationData['existedQty'] * $perProductTaxCost;
                                } else {
                                    $orderCalculationData['totalTax'] = 0;
                                }
                                
                            }

                            if (property_exists($etsyReceipt, "discount_amt") && property_exists($etsyReceipt->discount_amt, "amount")) {
                                
                                $orderTotalDiscount = $etsyReceipt->discount_amt->amount/$etsyReceipt->discount_amt->divisor;
                                
                                if ($orderTotalDiscount != 0) {
                                    $perProductDiscount = $orderTotalDiscount / $orderCalculationData['totalQty'];
                                    $orderCalculationData['totalDiscount'] = $orderCalculationData['existedQty'] * $perProductDiscount;
                                } else {
                                    $orderCalculationData['totalDiscount'] = 0;
                                }
                                
                            }

                            $orderTotalAmt = ($orderCalculationData['subTotal'] + $orderCalculationData['totalShipping'] + $orderCalculationData['totalTax']) - $orderCalculationData['totalDiscount']; 

                            $orderPaymentStatus = (property_exists($etsyReceipt, "is_paid") && $etsyReceipt->is_paid == TRUE) ? "PAID" : "NOT_PAID" ;

                            $dataToSend = [
                                "order" => [
                                    //"currency" => "USD",
                                    //"weightUnit" => "KG",
                                    "totals" => [
                                        "subtotal" => (string) $orderCalculationData['subTotal'], // before tax
                                        "shipping" => (string) $orderCalculationData['totalShipping'], // Total shipping price, before tax.
                                        "tax" => (string) $orderCalculationData['totalTax'], // tax
                                        "discount" => (string) $orderCalculationData['totalDiscount'], //Total calculated discount value.
                                        "total" => (string) $orderTotalAmt, //Total price charged.
                                    ],
                                    "channelInfo" => [
                                        "type" => "OTHER_PLATFORM",
                                        "externalOrderId" => property_exists($etsyReceipt, "receipt_id") ? (string) $etsyReceipt->receipt_id : ""
                                    ],
                                    "paymentStatus" => $orderPaymentStatus,

                                ]
                            ];
                            if ($etsyOrder->getWixOrderId()) {
                                
                                $wixOrderResponse[] = [
                                    "type" => "danger",
                                    "message" => $this->container->get('translator')->trans("order_already_exist_on_wix")
                                ];

                            } else {
                                $wixOrderResponse[] = $this->addOrderToWix($companyApplication, $etsyOrder, $etsyReceipt, $dataToSend, $existedProducts);
                            }
                        }
                    }

                    $this->entityManager->flush();

                    return array(
                        'totalCount' => property_exists($shopReceipts, "count") ? $shopReceipts->count : 0,
                        'items' => $shopReceipts,
                        'notifications' => $wixOrderResponse,
                    );
                } else {
                    return array(
                        'totalCount' => 0,
                        'items' => [],
                        'notifications' => [
                            "type" => "danger",
                            "message" => $this->container->get('translator')->trans("something_went_wrong_with_etsy_receipts_data")
                        ],
                    );
                }
            } else {

                return array(
                    'totalCount' => 0,
                    'items' => [],
                    'notifications' => [
                        "type" => "danger",
                        "message" => $this->container->get('translator')->trans("something_went_wrong_with_etsy_receipts_data")
                    ],
                );
            }
        } else {

            return array(
                'totalCount' => 0,
                'items' => [],
                'notifications' => [
                    "type" => "danger",
                    "message" => $this->container->get('translator')->trans("etsy_authentication_issue")
                ],
            );
        }
    }

    public function getEtsyOrder($params = [])
    {
        $etsyOrderRepo = $this->entityManager->getRepository(Orders::class);
        $etsyOrder = $etsyOrderRepo->findOneBy($params); 

        return $etsyOrder;
    }

    public function addOrderToWix($companyApplication, $etsyOrderObj, $etsyOrder, $params = [], $orderProducts = [])
    {
        $catalogHelper = $this->getAppHelper("catalog");

        $params['order']['lineItems'] = [];

        $params['order']['billingInfo'] = [
            "address" => [
                "email" => property_exists($etsyOrder, "buyer_email") ? $etsyOrder->buyer_email : "",
                "fullName" => [
                    "firstName" => property_exists($etsyOrder, "name") ? $etsyOrder->name : "",
                ],
                "country" => property_exists($etsyOrder, "country_iso") ? $etsyOrder->country_iso : "",
                "subdivision" => property_exists($etsyOrder, "state") ? $etsyOrder->state : "",
                "city" => property_exists($etsyOrder, "city") ? $etsyOrder->city : "",
                "zipCode" => property_exists($etsyOrder, "zip") ? $etsyOrder->zip : "",
                "addressLine1" => property_exists($etsyOrder, "first_line") ? $etsyOrder->first_line : "",
            ]
        ];

        $params['order']['shippingInfo'] = [
            "shipmentDetails" => [
                "address" => [
                    "email" => property_exists($etsyOrder, "buyer_email") ? $etsyOrder->buyer_email : "",
                    "fullName" => [
                        "firstName" => property_exists($etsyOrder, "name") ? $etsyOrder->name : "",
                    ],
                    "country" => property_exists($etsyOrder, "country_iso") ? $etsyOrder->country_iso : "",
                    "subdivision" => property_exists($etsyOrder, "state") ? $etsyOrder->state : "",
                    "city" => property_exists($etsyOrder, "city") ? $etsyOrder->city : "",
                    "zipCode" => property_exists($etsyOrder, "zip") ? $etsyOrder->zip : "",
                    "addressLine1" => property_exists($etsyOrder, "first_line") ? $etsyOrder->first_line : "",
                ]
            ]
        ];
        
        foreach ($orderProducts as $orderProduct) {

            $productDetail = $catalogHelper->get_product([
                "etsy_listing_id" => property_exists($orderProduct, "listing_id") ? $orderProduct->listing_id : ""
            ]);

            $variantsData = property_exists($orderProduct, "product_data") ? $orderProduct->product_data : [];

            $productPrice = property_exists($orderProduct, "price") ? ( property_exists($orderProduct->price, "amount") ? $orderProduct->price->amount / $orderProduct->price->divisor : "") : "";

            $productOptions = [];

            foreach ($variantsData as $variant) {

                $optionValues = property_exists($variant, "values") ? $variant->values : [];
                $optionValue = isset($optionValues[0]) ? $optionValues[0] : "";

                $productOptions[] = [
                    "option" => property_exists($variant, "property_name") ? $variant->property_name : "",
                    "selection" =>  $optionValue
                ];
            }

            $params['order']['lineItems'][] = [
                "productId" => !empty($productDetail) ? $productDetail->getWixProdId() : "",
                "lineItemType" => "PHYSICAL",
                "name" => property_exists($orderProduct, "title") ? $orderProduct->title : "",
                "quantity" => property_exists($orderProduct, "quantity") ? $orderProduct->quantity : "",
                "priceData" => [
                    "price" => (string) $productPrice
                ],
                "options" => $productOptions
            ];
        }
        
        list($orderResponse, $error) = $this->wixClient->create_order($params);

        $orderResponse = json_decode($orderResponse);

        if (!empty($orderResponse)) {

            $wixOrderDetail = property_exists($orderResponse, "order") ? $orderResponse->order : (object)[];
            $wixOrderId = property_exists($wixOrderDetail, "id") ? $wixOrderDetail->id : "";
            $wixOrderNumber = property_exists($wixOrderDetail, "number") ? $wixOrderDetail->number : "";

            $etsyOrderObj->setWixOrderId($wixOrderId);
            $etsyOrderObj->setWixOrderNo($wixOrderNumber);
            $etsyOrderObj->setSyncStatus(1); # 1 For Synced on Wix.

            $this->entityManager->persist($etsyOrderObj);

            return [
                "type" => "success",
                "message" => $this->container->get('translator')->trans("order_synced_successfully")
            ];

        } else {

            $etsyOrderObj->setSyncStatus(2); # 2 For Not Synced on Wix.
            $this->entityManager->persist($etsyOrderObj);

            return [
                "type" => "danger",
                "message" => $this->container->get('translator')->trans("order_not_synced_successfully")
            ];
        }

    }

    public function getCategoriesFromEtsy($companyApplication, $params = [])
    {   
        $etsyAuthData = $this->reRequestAccessToken($companyApplication); 

        if (!empty($etsyAuthData)) {
            
            list($taxonomyData, $error) = $this->etsyClient->getSellerTaxonomyNodes([
                'client_id' => $etsyAuthData->getClientId()
            ]);
            $taxonomyData = json_decode($taxonomyData); 

            if (property_exists($taxonomyData, "results") && !empty($taxonomyData->results)) {
                return [$taxonomyData->results, ""];
            } else {
                return [(object)[], ""];
            }

        } else {

            $error = [
                "msg" => $this->container->get('translator')->trans("not_authenticated"),
                "type" => "danger"
            ];
            return [(object)[], $error];
        }
    }

    public function getCategoriesFromWix($companyApplication, $params = [])
    {
        if (empty($params['page'])) {
            $params['page'] = 1;
            $params['allCategories'] = [];
        }
        if (empty($params['limit'])) {
            $params['limit'] = 100;
        }
        
        $apiParams = [
            'query' => [
                'paging' => [
                    'limit' =>  $params['limit'],
                    'offset' => ($params['page'] - 1) * $params['limit'],
                ]
            ]
        ];
        
        list($categories, $error) = $this->wixClient->get_collections($apiParams);
        $categories = json_decode($categories);

        $apiTotalResults = property_exists($categories, "totalResults") ? $categories->totalResults : 0;
        $apiOffset = property_exists($categories, "metadata") ? ( property_exists($categories->metadata, "offset") ? $categories->metadata->offset : 0 ) : 0;

        $categories = property_exists($categories, "collections") ? $categories->collections : [];

        $params['allCategories'] = array_merge($params['allCategories'], $categories);
                
        if (count($params['allCategories']) < $apiTotalResults) {
            
            $params['page'] = $params['page'] + 1;
            $allCategories = $this->getCategoriesFromWix($companyApplication, $params);
            return $allCategories;
        }
        
        return $params['allCategories'];

        // if (property_exists($categories, "collections") && !empty($categories->collections)) {
        //     return $categories->collections;
        // } else {
        //     return [];
        // }
    }

    public function getMappedCategory($companyApplication, $wixProductCollections = [])
    {
        $catalogHelper = $this->getAppHelper("catalog");

        $defaultEtsyCategorySetting = $this->getSettingValue([
            "company_application" => $companyApplication,
            "setting_name" => "etsy_category"
        ]);
        $defaultEtsyCategory = $catalogHelper->getEtsyCategory($params = [ 'id' => $defaultEtsyCategorySetting->getSettingValue() ]);

        if($companyApplication->getCompany()->getStoreHash() == 'BlueRidgePrintinge77a'
            || $companyApplication->getCompany()->getStoreHash() == 'DanBrianneDesigns6677'
        ){
            $defaultEtsyCategory = $catalogHelper->getEtsyCategory($params = [ 'id' => $defaultEtsyCategorySetting->getSettingValue() ]);
        }

        $etsyMappedCategories = [];

        foreach ($wixProductCollections as $wixCollection) {
            if ($wixCollection != "00000000-000000-000000-000000000001" ) {
                
                $wixCategory = $catalogHelper->getWixCategory([
                    "company_application" => $companyApplication,
                    "category_id" => $wixCollection
                ]);
                
                if (!empty($wixCategory)) {
                    
                    $categoryMapping = $catalogHelper->getCategoryMapping([
                        "company_application" => $companyApplication,
                        "wix_category" => $wixCategory
                    ]);
                    
                    if (!empty($categoryMapping)) {

                        $etsyMappedCategories[] = $categoryMapping->getEtsyCategory()->getCategoryId();

                    }
                }
            }
        }

        if (in_array($defaultEtsyCategory->getCategoryId(), $etsyMappedCategories)) {

            $etsyCategoryId = $defaultEtsyCategory->getCategoryId();

        } else {
            
            $etsyCategoryId = isset($etsyMappedCategories[0]) ? $etsyMappedCategories[0] : $defaultEtsyCategory->getCategoryId();
        }

        return isset($etsyCategoryId) ? $etsyCategoryId : $defaultEtsyCategory->getCategoryId();
        
    }

    public function getSettingValue($params = [])
    {
        $settingValueRepo = $this->entityManager->getRepository(SettingValue::class);
        $settingValueData = $settingValueRepo->findOneBy($params); 

        return $settingValueData;
    }

    public function getSettingValues($params = [])
    {
        $settingValueRepo = $this->entityManager->getRepository(SettingValue::class);
        $settingValueData = $settingValueRepo->findBy($params); 

        return $settingValueData;
    }

    public function updateSettingValue($settingValue, $params = [])
    {
        $settingValue = !empty($settingValue) ? $settingValue : new SettingValue;

        if (isset($params['companyApplication'])) {
            $settingValue->setCompanyApplication($params['companyApplication']);
        }

        if (isset($params['company'])) {
            $settingValue->setCompany($params['company']);
        }

        if (isset($params['settingValue'])) {
            $settingValue->setSettingValue($params['settingValue']);
        }

        if (isset($params['settingName'])) {
            $settingValue->setSettingName($params['settingName']);
        }
        
        $settingValue->setUpdatedAt(time());

        $em = $this->entityManager;
        $em->persist($settingValue);
        $em->flush();

        return $settingValue;
    }

    public function updateEtsyInventory($companyApplication, $productData = [])
    {
        $catalogHelper = $this->getAppHelper("catalog"); 

        $etsyAuthData = $this->reRequestAccessToken($companyApplication);
        $params['client_id'] = !empty($etsyAuthData) ? $etsyAuthData->getClientId() : "";
        $params['AccessToken'] = !empty($etsyAuthData) ? $etsyAuthData->getAccessToken() : "";

        $wixProductId = property_exists($productData, "productId") ? $productData->productId : "";

        $product = $catalogHelper->get_product(["wix_prod_id" => $wixProductId]);
        if(is_null($product)) {
            return;
        }
        $params['listingId'] = $product->getEtsyListingId();

        if (!empty($params['listingId'])) {

            $wixClient = new WixClient($companyApplication);

            list($wixProduct, $error) = $wixClient->get_product($wixProductId);
            $wixProductObj = json_decode($wixProduct);
            
            $wixProduct = property_exists($wixProductObj, "product") ? $wixProductObj->product : [];

            $productVariants = property_exists($wixProduct, "variants") ? $wixProduct->variants : [];

            $manageVariants = property_exists($wixProduct, "manageVariants") ? $wixProduct->manageVariants : false;

            $productVariantsData = $this->arrangeProductVariants($companyApplication, $productVariants, $manageVariants);
            
            if (!empty($productVariantsData)) {

                list($etsyProductListingInventory, $error) = $this->etsyClient->updateListingInventory($productVariantsData, $params);
                $etsyProductListingInventory = json_decode($etsyProductListingInventory);

            } else {

                if (is_object($wixProduct)) {

                    $stock = property_exists($wixProduct, "stock") ? $wixProduct->stock : (object)[];
                    $quantity = property_exists($stock, "quantity") ? $stock->quantity : 0; 

                    $priceData = property_exists($wixProduct, "price") ? $wixProduct->price : (object)[];
                    $price = property_exists($priceData, "price") ? $priceData->price : 0; 

                    $variantProductData['products'][] = [
                        "offerings" => [
                            [
                                "price" => (string) $price,
                                "is_enabled" => property_exists($wixProduct, "visible") ? $wixProduct->visible : false,
                                "quantity" => $quantity,
                            ]
                        ]
                    ];
                    
                    list($etsyProductListingInventory, $error) = $this->etsyClient->updateListingInventory($variantProductData, $params);
                    $etsyProductListingInventory = json_decode($etsyProductListingInventory);
                }          
            }
        }

        return TRUE;
    }

    public function generateEtsyChallengeCode()
    {
        $string = $this->createEtsyNonce(32); 
        
        $verifier = $this->etsyStringBase64encode(
            pack("H*", $string)
        );
            
        $code_challenge = $this->etsyStringBase64encode(
            pack("H*", hash("sha256", $verifier))
        );
        
        return [$verifier, $code_challenge];
    }

    public function createEtsyNonce(int $bytes = 12)
    {
        return bin2hex(random_bytes($bytes));
    }

    public function etsyStringBase64encode($string)
    {
        return strtr(
            trim(
                base64_encode($string),
                "="
            ),
            "+/", "-_"
        );
        
    }
}