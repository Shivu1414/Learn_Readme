<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

use Doctrine\DBAL\DBALException;
use Webkul\Modules\Wix\WixmpBundle\Entity\CustomFeilds;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout;
use Webkul\Modules\Wix\WixmpBundle\Entity\Setting;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Webkul\Modules\Wix\WixmpBundle\Entity\PayoutCommissions;

class SellerHelper extends WixMpBaseHelper
{
    public function get_seller_plan_condition()
    {
        return array(
               'max_product' => array(
                    'type' => 'text',
                    'default' => 5,
                    'label_class' => 'wk-required wk-integer',
                    'class' => null,
                ),
                'commission_type' => array(
                    'type' => 'checkbox',
                    'default' => 0,
                    'label_class' => 'form-check-label',
                    'class' => 'form-check-input',
                ),
                'commission' => array(
                    'type' => 'text',
                    'default' => 10,
                    'label_class' => 'wk-required wk-price',
                    'class' => null,
                ),
                'commission_value_type' => array(
                    'type' => 'select',
                    'default' => 'percentage',
                    'label_class' => 'wk-required',
                    'class' => null,
                )
        );
    }

    public function update_seller_plan($plan, $params)
    {
        if (isset($params['company'])) {
            $plan->setCompany($params['company']);
        }

        if (isset($params['status'])) {
            $plan->setStatus($params['status']);
        }
        
        $plan->SetUpdatedAt(time());
        $em = $this->entityManager;
        $em->persist($plan);
        $em->flush();
        
        return $plan;
    }

    public function get_seller_plans($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $plan_repo = $this->entityManager->getRepository(SellerPlan::class);
        $plan_list = $plan_repo->getPlans($params);

        return array($plan_list, $params);
    }

    public function get_seller_plan($params)
    {
        $plan_repo = $this->entityManager->getRepository(SellerPlan::class);
        $plan_list = $plan_repo->findOneBy($params);

        return $plan_list;
    }

    public function delete_seller_plan($plan)
    {
        $response = [];
        try {
            $this->entityManager->remove($plan);
            $this->entityManager->flush();
            // $this->add_notification('success', $this->container->get('translator')->trans('item_deleted_successfully'));
        } catch (DBALException $e) {
            $sql_error_code = $e->getPrevious()->getCode();
            if ($sql_error_code == '23000') {
                // $this->add_notification('danger', $this->container->get('translator')->trans('cannot_delete_this_item_already_in_use'));
                $response['error_code'] = $sql_error_code;
                $response['error'] = $this->container->get('translator')->trans('cannot_delete_this_item_already_in_use');
            } else {
                // $this->add_notification('danger', $this->container->get('translator')->trans('cannot_delete_this_item'));
                $response['error_code'] = $sql_error_code;
                $response['error'] = $this->container->get('translator')->trans('cannot_delete_this_item');
            }
        }

        return $response;
    }

    public function get_all_sellers($params)
    {
        $default_params = [
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $seller_repo = $this->entityManager->getRepository(Seller::class);
        $seller_list = $seller_repo->getAllSellers($params);

        return $seller_list;
    }

    public function get_plan_list($company, $status = null)
    {
        $_plan = [];
        $plan_repo = $this->entityManager->getRepository(SellerPlan::class);
        $params = ['company' => $company];
        if ($status != null) {
            $params['status'] = $status;
        }
        $plan_list = $plan_repo->findBy($params);

        $commonHelper = $this->container->get('app.runtime')->getHelper('common');

        foreach ($plan_list as $plan) {
            // dump($plan);
            $plan_str = $plan->getPlan();

            // $price = $plan->getPrice() > 0 ? $plan->getPrice() :'FREE';
            $plan_price = $commonHelper->get_format_price_simple($plan->getPrice(), 'admin', $company->getStorehash());
            $plan_str .= ' - '.$this->get_plan_interval_str($plan);

            $plan_str .= ' - '.$this->get_plan_products_str($plan);

            $plan_str .= ' ('.$plan_price.')';
            $_plan[$plan_str] = $plan->getId();
        }

        return $_plan;
    }

    public function get_plan_interval_str($plan)
    {
        $interval_str = '';
        if ($plan->getIntervalType() == 'Y') {
            $interval_str = $this->_trans('yearly');
        } elseif ($plan->getIntervalType() == 'H') {
            $interval_str = $this->_trans('half-yearly');
        } elseif ($plan->getIntervalType() == 'Q') {
            $interval_str = $this->_trans('quarterly');
        } elseif ($plan->getIntervalType() == 'M') {
            $interval_str = $this->_trans('monthly');
        } elseif ($plan->getIntervalType() == 'W') {
            $interval_str = $this->_trans('Weekly');
        } else {
            $interval_str = $plan->getIntervalValue().' '.$this->_trans('days');
        }

        return $interval_str;
    }

    public function get_plan_products_str($plan)
    {
        $planConditions = $plan->getConditions();
        $planProductStr = '';
        if (isset($planConditions['max_product']) && !empty($planConditions['max_product'])) {
            $planProductStr .= $planConditions['max_product'].' '.$this->_trans('products');
        } elseif (isset($planConditions['max_product']) && $planConditions['max_product'] == 0) {
            $planProductStr .= $this->_trans('unlimited').' '.$this->_trans('products');
        }

        return $planProductStr;
    }

    public function update_seller($seller, $params)
    {
        $update_plan = false;
        if (isset($params['company'])) {
            $seller->setCompany($params['company']);
        }

        if (isset($params['status'])) {
            $seller->setStatus($params['status']);
        }

        if (isset($params['current_plan'])) {
            $seller->setCurrentPlan($params['current_plan']);

            if ($params['current_plan']->getIntervalType() == 'Y') {
                $added_timestamp = 3600 * 24 * 365;
            } elseif ($params['current_plan']->getIntervalType() == 'H') {
                $added_timestamp = 3600 * 24 * 180;
            } elseif ($params['current_plan']->getIntervalType() == 'Q') {
                $added_timestamp = 3600 * 24 * 90;
            } elseif ($params['current_plan']->getIntervalType() == 'M') {
                $added_timestamp = 3600 * 24 * 30;
            } elseif ($params['current_plan']->getIntervalType() == 'W') {
                $added_timestamp = 3600 * 24 * 7;
            } else {
                $added_timestamp = 3600 * 24 * $params['current_plan']->getIntervalValue();
            }
            $seller->setExpireAt(time() + $added_timestamp);
            $update_plan = true;
        }

        if (isset($params['expire_at'])) {
        }

        $seller->SetUpdatedAt(time());
        $em = $this->entityManager;
        $em->persist($seller);
        $em->flush();

        if ($update_plan && $seller->getId()) {
            $payout = new SellerPayout();

            $data = array(
                'payout_type' => 'P',
                'comment' => 'payment for plan:'.$params['current_plan']->getPlan(),
                'order_amount' => 0.00,
                'payout_amount' => $params['current_plan']->getPrice(),
                'commission' => 0.00,
                'commission_amount' => 0.00,
                'commission_type' => 'P',
                'status' => 'A',
                'plan' => $params['current_plan'],
                'company' => $params['company'],
                'seller' => $seller,
                'order_id' => null,
            );
            $payout = $this->update_seller_payout($payout, $data);
        }

        return $seller;
    }

    public function get_sellers($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $seller_repo = $this->entityManager->getRepository(Seller::class);
        $seller_list = $seller_repo->getSeller($params);

        return array($seller_list, $params);
    }

    public function get_seller($params)
    {
        $seller_repo = $this->entityManager->getRepository(Seller::class);
        $seller = $seller_repo->findOneBy($params);

        return $seller;
    }

    public function disable_seller($request, $companyApplication)
    {
        $params = $request->request->all();
        $queryParams = $request->query->all();

        $platformHelper = $this->getAppHelper("platform");

        $notifications = [];
        if (!isset($queryParams['entity_id']) || empty($queryParams['entity_id'])) {
            $notifications[] = array('type' => 'danger', 'message' => 'No seller found to disable');

            return array(
                'totalCount' => 0,
                'items' => 0,
                'notifications' => $notifications,
            );
        }
        $company = $companyApplication->getCompany();
        $catalogHelper = $this->getAppHelper('catalog');
        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0
        // check in cache : products
        $hasCacheProductData = $this->cache->hasItem('seller_products_data_'.$company->getId());
        //get cahce id
        $cacheProductData = $this->cache->getItem('seller_products_data_'.$company->getId());
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
        if ($start == 0) {
            //clear cache if exists to prevent duplicacy
            $this->cache->deleteitem('seller_products_data_'.$company->getId());
        }
        //$batch = (int) $params['limit'] + (int) $start;
        // if ($batch > $productsData['totalCount']) {
        //     $batch = $productsData['totalCount'];
        // }
        $seller_data = $this->get_seller(['id' => $queryParams['entity_id']]);

        // check if batch requested is not in cache : requestApi
        if ($requestApi) {
            //either product data is empty or less then required
            //request API : get product from MP
            list($products, $filters) = $catalogHelper->get_products(
                array(
                    'page' => $productsData['page'],
                    'items_per_page' => 250,
                    'company' => $company,
                    'seller' => $queryParams['entity_id'],
                )
            );
            
            if ($products->getTotalItemCount()) {
                $productsData['items'] = array_merge($productsData['items'], $products->getItems());
                ++$productsData['page'];
                $productsData['totalCount'] = $products->getTotalItemCount();
                //save to cache
                $cacheProductData->set($productsData);
                $isSaved = $this->cache->save($cacheProductData);
            }
        }
        // process batch
        //$toProcessProducts = array_slice($productsData['items'], $start, $params['limit']);
        $toProcessProducts = $productsData['items'];

        if (!empty($toProcessProducts)) {
            //$productChunks = array_chunk($toProcessProducts, 10);

            $data = [];
            $data['product']['visible'] = false;
            $pIds = [];
            $is_success = true;
            // product repo to disable mp products
            $product_repo = $this->entityManager->getRepository(Products::class);
            foreach ($toProcessProducts as $product) { // in case limit is more than 10 : BC only allow 10 products in batch update
                $platform_product_data = [];
                // foreach ($batchProducts as $product) {
                //     $pIds[] = $product->getId();
                //     $seller = $product->getSeller();
                //     $additionalData = [
                //         'id' => $product->getProdId(),
                //     ];
                //     $platform_product_data[] = array_merge($data, $additionalData);
                // }
                $pIds[] = $product->getId();
                $seller = $product->getSeller();
                $additionalData = [
                    'id' => $product->getProdId(),
                ];
                //$platform_product_data = array_merge($data, $additionalData);
                $platform_product_data = $data;
                
                if (!empty($platform_product_data)) {
                    // bulk update : only 10 at a time : BC Limitation
                    $response = $platformHelper->update_platform_product($product->getProdId(), $platform_product_data);
                    $response = (is_array($response) && isset($response[0]) ) ? json_decode($response[0]) : (object) array();
                    
                    if (isset($response->product) && isset($response->product->id)) {
                        // platform product successsfully disabled : perform action
                        $is_success = true;
                    } else {
                        $is_success = false;
                        
                        if (isset($response->message) && !empty($response->message)) {
                            $notifications['danger'][] = $response->message;
                        }
                    }
                } else {
                    $notifications[] = ['type' => 'danger', 'message' => $this->container->get('translator')->trans('message.invalid_data_for_bulk_action')];
                    $is_success = false;
                }
            }
            if (!empty($pIds)) {
                // disable MP products : from MP REPO
                $product_repo->bulkUpdate($company, array('status_to' => 'D'), $pIds);
                $this->entityManager->flush();
            }
        }

        // clear cache on last batch & Disable seller if last batch
        //if ($batch >= $productsData['totalCount']) {
            $UpdatedSeller = $this->update_seller($seller_data, ['status' => 'D']);
            if ($UpdatedSeller->getStatus() == 'D') {
                $notifications[] = ['type' => 'success', 'message' => $this->container->get('translator')->trans('message.common.product_status_changed_successfully')];
                //trigger status change event
                $SellerEvent = new SellerEvent($companyApplication, $UpdatedSeller);
                $this->dispatcher->dispatch(
                    $SellerEvent,
                    SellerEvent::WIX_SELLER_STATUS_CHANGE
                );
            } else {
                $notifications[] = ['type' => 'danger', 'message' => $this->container->get('translator')->trans('message.common.unable_to_change_status')];
            }
            // remove from cache
            //$this->cache->deleteitem('products_export_data_'.$company->getId());
        //}

        return array(
            'totalCount' => $productsData['totalCount'],
            'items' => [],
            'notifications' => $notifications,
        );
    }

    public function get_seller_plan_validated_data($seller)
    {
        $current_plan = $seller->getCurrentPlan();
        $plan_conditions = $current_plan->getConditions();
        $validation_data = false;
        if ($current_plan) {
            $validation_data['transaction_fees'] = $current_plan->getPrice();
            // $validation_data['categories'] = $current_plan->getCategories() == null ? null:json_decode($current_plan->getCategories());
            $validation_data['plan_state'] = 'E';  //Active or Expired
            $validation_data['max_email'] = 0;
            $validation_data['max_product'] = $plan_conditions['max_product'];
            $validation_data['commission'] = $plan_conditions['commission'];
            $validation_data['current_sale'] = 0;
            $validation_data['current_seller'] = 0;
            $validation_data['current_product'] = 0;
            $validation_data['plan_upto'] = 0;
            $validation_data['allow'] = array(
                'product' => false,
                'seller' => false,
                // 'mail_template' => $current_plan->getAllowEmailTemplating(),
                // 'custom_theme' => $current_plan->getAllowThemeCustomization(),
                // 'reporting' => $current_plan->getAllowReport(),
                // 'custom_vendor_fields' => $current_plan->getAllowCustomFields(),
            );
            // get adminstrator count
            $UserHelper = $this->getAppHelper('user');
            $sellerUser = $UserHelper->get_all_users(['user_type' => 'S', 'count' => 'Y', 'get_single_result' => 'Y', 'company' => $seller->getCompany(), 'seller' => $seller]);
            //TODO : INVALID CODE SHOULD BE FOR SELLER/USER INSTEAD OF PRODUCT
            // if ($validation_data['max_product'] == 0 || $sellerUser[1] < $validation_data['max_product']) {
            //     $validation_data['allow']['product'] = true;
            // } else {
            //     $validation_data['allow']['product'] = false;
            // }
            $validation_data['current_seller'] = $sellerUser[1];
            // get product count
            $catalogHelper = $this->getAppHelper('catalog');
            $product_count = $catalogHelper->get_product_count($seller->getCompany()->getId(), $seller->getId());

            if ($validation_data['max_product'] == 0 || $product_count[1] < $validation_data['max_product']) {
                $validation_data['allow']['product'] = true;
            } else {
                $validation_data['allow']['product'] = false;
            }
            $validation_data['current_product'] = $product_count[1];
        }

        return $validation_data;
    }

    public function delete_sellers($seller_ids)
    {
        $success = true;
        try {
            $notifications = [];
            $em = $this->entityManager;
            if (!empty($seller_ids)) {
                foreach ($seller_ids as $sellerId) {
                    $seller = $this->get_seller(array('id' => $sellerId));
                    if (!empty($seller)) {
                        $em->remove($seller);
                    }
                }
            }
            $em->flush();
        } catch (DBALException $e) {
            $success = false;
            $sql_error_code = $e->getPrevious()->getCode();
            if ($sql_error_code == '23000') {
                $notifications[] = [
                    'type' => 'danger',
                    'message' => $this->container->get('translator')->trans(
                        'cannot_delete_this_item_already_in_use_%seller_name',
                        array('seller_name' => $seller->getSeller())
                    ),
                ];
            } else {
                $notifications[] = [
                    'type' => 'danger',
                    'message' => $this->container->get('translator')->trans(
                        'cannot_delete_this_item_%seller_name',
                        array('seller_name' => $seller->getSeller())
                    ),
                ];
            }
        }

        return [$notifications, $success];
    }

    public function create_order_commission($order,$productDetails)
    { 
        $catalogHelper = $this->getAppHelper('catalog');
        $payouts = new SellerPayout();
        $seller = $order->getSeller(); 
        $company = $order->getCompany();
        $current_plan = $seller->getCurrentPlan(); 
        $commission = isset($current_plan->getConditions()['commission']) ? $current_plan->getConditions()['commission'] : 0;
        $commission_value_type = isset($current_plan->getConditions()['commission_value_type']) ? $current_plan->getConditions()['commission_value_type'] : "";
        
        //$isCommPerProduct = isset($current_plan->getConditions()['commission_per_product']) ? $current_plan->getConditions()['commission_per_product'] : 0;
        $customization_store = ['kiff.co0e45','ACotswoldLifestylea3d4','MySiteec6f','PurpleBookHouse7c95'];
        $commissionType = isset($current_plan->getConditions()['commission_type']) ? $current_plan->getConditions()['commission_type'] : "commission_per_product";
      
        $categoryCommissionRateType = isset($current_plan->getConditions()['category_comission_rate_type']) ? $current_plan->getConditions()['category_comission_rate_type'] : "highest_rate";
        
        $order_total = $order->getTotal();
   
        $isCommPerProduct = 0;
        $isCommPerCategory = 0;

        $customization_store = ['kiff.co0e45','ACotswoldLifestylea3d4','PurpleBookHouse7c95'];
        if ($current_plan == null) {

            $commission_percentage = 0.00;
            $commission_amount = 0.00;
            $isCommPerProduct = 0;

        }
        switch ($commissionType) {
            
            case "commission_per_order":
                $isCommPerProduct = 0;
                $commission_percentage = $commission;

                if (in_array($order->getCompany()->getStoreHash(),$customization_store)) {
                    $order_total = ( $order->getSubtotal() + $order->getTax() ) - $order->getDiscount();
                }
                // $commission_amount = (isset($productCommission) && $productCommission) ? $productCommission : ($commission_percentage * $order_total) / 100;
               
                
                
                if($order->getCompany()->getStoreHash() == 'DTMasterCarbon99fa'){
                    if(isset($commission_value_type) &&  $commission_value_type == 'percentage') {
                        $commission_amount = $order_total * $commission_percentage / 100;
                    } else {
                        $commission_amount =  $commission_percentage;
                    }
                } else {
                    $commission_amount = ($commission_percentage * $order_total) / 100;
                    $commission_amount = ($isCommPerProduct) ? $productCommission : $commission_amount;
                }
                // $commission_amount = ($commission_percentage * $order_total) / 100;
                // $commission_amount = ($isCommPerProduct) ? $productCommission : $commission_amount;
                //$order_amt = ($isCommPerProduct) ? $order->getSubtotal() : $order_total;
               
                $order_amt = $order_total;
            break;
            
            case "commission_per_product":
                
                $isCommPerProduct = 1;
                $commission_percentage = $commission;
                $productCommission = 0;
                $productTotalPrice = 0;

                foreach ($productDetails as $product) {
                    
                    $pCommission = 0;
                    $qty = !is_null($product->getQuantity()) ? $product->getQuantity() : 1;
                    $commission = ($product->getCommission() != null) ? $product->getCommission() : 0;
                    
                    if ($product->getCommissionType() == "percentage") {

                        $pCommission = (( $product->getPrice() / 100 ) * $commission) * $qty;
                        $productCommission += $pCommission;

                    } elseif ($product->getCommissionType() == "fixed") {

                        $pCommission = $commission * $qty;
                        $productCommission += $pCommission;

                    } else {

                        $pCommission = $commission * $qty;
                        $productCommission += $pCommission;
                    }
                    
                    $productsPayoutCommissionData = [
                        'product' => $product,
                        'order' => $order,
                        'commission_type' => 'commission_per_product',
                        'commission_rate' => $commission,
                        'commission_amount' => $pCommission
                    ];

                    $this->update_payout_commission($productsPayoutCommissionData);
                }

                $commission_amount = $productCommission;
                $order_amt = $order_total;
            break;
            
            case "commission_per_category":

                $categoryCommission = 0;
                $isCommPerCategory = 1;

                $productsPayoutCommissionData = [];
                
                foreach ($productDetails as $product) {

                    $catCommission = 0;

                    $categories = $product->getCategoryData();
                    
                    $categoryCommissionRates = [];
                    foreach($categories as $category) {
                        
                        $categoryData = $catalogHelper->getWixMpCategory([
                            '_collectionId' => $category,
                            'company' => $company
                        ]);

                        $categoryCommissionRates[] = $categoryData->getComission();
                    }

                    ($categoryCommissionRateType == "highest_rate") ? rsort($categoryCommissionRates) : sort($categoryCommissionRates);
                    $categoryCommissionRate = isset($categoryCommissionRates[0]) ? $categoryCommissionRates[0] : 0;
                    
                    $qty = !is_null($product->getQuantity()) ? $product->getQuantity() : 1;
                    $catCommission = (( $product->getPrice() / 100 ) * $categoryCommissionRate) * $qty;
                    $categoryCommission += $catCommission;

                    $productsPayoutCommissionData = [
                        'product' => $product,
                        'order' => $order,
                        'commission_type' => 'commission_per_category',
                        'commission_rate' => $categoryCommissionRate,
                        'commission_amount' => $categoryCommission
                    ];

                    $this->update_payout_commission($productsPayoutCommissionData);
                }
                
                $commission_amount = $categoryCommission;
                $order_amt = $order_total;
                $commission_percentage = $categoryCommissionRate;

            break;

            default:

                # SAME LOGIC AS THE COMMISSION PER ORDER CASE

                $isCommPerProduct = 0;
                $commission_percentage = $commission;

                if (in_array($order->getCompany()->getStoreHash(),$customization_store)) {
                    $order_total = ( $order->getSubtotal() + $order->getTax() ) - $order->getDiscount();
                }

                // $commission_amount = (isset($productCommission) && $productCommission) ? $productCommission : ($commission_percentage * $order_total) / 100;
                $commission_amount = ($commission_percentage * $order_total) / 100;
                $commission_amount = ($isCommPerProduct) ? $productCommission : $commission_amount;
                
                //$order_amt = ($isCommPerProduct) ? $order->getSubtotal() : $order_total;
                $order_amt = $order_total;
                
            break;
        }
        
        $data = [
            'payout_type' => 'O',
            'comment' => null,
            'order_amount' => $order_amt,
            'payout_amount' => 0.00,
            'commission' => $commission_percentage,
            'commission_amount' => $commission_amount,
            'commission_type' => 'P',
            'status' => 'P',
            'plan' => null,
            'company' => $company,
            'seller' => $seller,
            'order_id' => $order->getId(),
            'is_commission_per_product' => $isCommPerProduct,
            'is_commission_per_category' => $isCommPerCategory
        ];
        
        $this->update_seller_payout($payouts, $data);
 
        return $payouts;
    }

    public function update_seller_payout($payout, $params)
    {
        if (isset($params['payout_type'])) {
            $payout->setPayoutType($params['payout_type']);
        }
        if (isset($params['comment'])) {
            $payout->setComment($params['comment']);
        }
        if (isset($params['order_amount'])) {
            $payout->setOrderAmount($params['order_amount']);
        }
        if (isset($params['payout_amount'])) {
            $payout->setPayoutAmount($params['payout_amount']);
        }
        if (isset($params['commission'])) {
            $payout->setCommission($params['commission']);
        }
        if (isset($params['commission_amount'])) {
            $payout->setCommissionAmount($params['commission_amount']);
        }
        if (isset($params['commission_type'])) {
            $payout->setCommissionType($params['commission_type']);
        }
        if (isset($params['status'])) {
            $payout->setStatus($params['status']);
        }
        if (isset($params['plan'])) {
            $payout->setPlan($params['plan']);
        }
        if (isset($params['company'])) {
            $payout->setCompany($params['company']);
        }
        if (isset($params['seller'])) {
            $payout->setSeller($params['seller']);
        }
        if (isset($params['order_id'])) {
            $payout->setOrderId($params['order_id']);
        }
        if (isset($params['is_commission_per_product'])) {
            $payout->setIsCommissionPerProduct($params['is_commission_per_product']);
        }
        if (isset($params['is_commission_per_category'])) {
            $payout->setIsCommissionPerCategory($params['is_commission_per_category']);
        }

        $payout->setUpdatedAt(time());
        $em = $this->entityManager;
        $em->persist($payout);
        $em->flush();

        return $payout;
    }

    public function get_seller_payout_calculation($company, $seller = null)
    {
        $payout_repo = $this->entityManager->getRepository(SellerPayout::class);
        $params = array(
            'company' => $company,
            'seller' => $seller,
            'payout_type' => 'O',
            'status' => ['A','C'],
        );
        $total_sale = $payout_repo->getSumOfPayout('orderAmount', $params);
        $params['status'] = 'A';
        $total_sale_balance = $payout_repo->getSumOfPayout('orderAmount', $params);
        $total_commission = $payout_repo->getSumOfPayout('commissionAmount', $params);
        $params['payout_type'] = 'P';
        $total_payout = $payout_repo->getSumOfPayout('payoutAmount', $params);
        $params['payout_type'] = 'W';
        $total_withdraw = $payout_repo->getSumOfPayout('payoutAmount', $params);
        // GET TOTAL COMPLETED ORDERS
        //$params['status'] = 'C';
        //$total_completed = $payout_repo->getSumOfPayout('allAmount', $params);
        return array(
            'total_sale' => $total_sale[1] == null ? 0.00 : $total_sale[1],
            'total_sale_balance' => $total_sale_balance[1] == null ? 0.00 : $total_sale_balance[1],
            'total_commission' => $total_commission[1] == null ? 0.00 : $total_commission[1],
            'total_payout' => $total_payout[1] == null ? 0.00 : $total_payout[1],
            'total_withdraw' => $total_withdraw[1] == null ? 0.00 : $total_withdraw[1],
        );
    }

    public function get_seller_list($company, $status = null)
    {
        $_seller = [];
        $seller_repo = $this->entityManager->getRepository(Seller::class);
        $seller_params = array(
            'company' => $company->getId(),
            'isArchieved' => 0
        );
        if (!empty($status)) {
            $seller_params['status'] = $status;
        }
        $seller_list = $seller_repo->findBy($seller_params);
        foreach ($seller_list as $seller) {
            $_seller[$seller->getSeller()] = $seller->getId();
        }

        return $_seller;
    }

    public function get_seller_payouts($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $plan_repo = $this->entityManager->getRepository(SellerPayout::class);
        $plan_list = $plan_repo->getPayouts($params);

        return array($plan_list, $params);
    }

    public function get_seller_payout($params)
    {
        $payout_repo = $this->entityManager->getRepository(SellerPayout::class);
        $payout_list = $payout_repo->findOneBy($params);

        return $payout_list;
    }

    public function performBatchActionForPayoutStatus($request, $formData, $companyApplication, $payout = null)
    {
        $notifications = [];
        $action = $formData['batch_action'];
        $payoutIds = $request->request->get('payout_ids');
        $company = $companyApplication->getCompany();
        if (empty($payoutIds)) {
            $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.empty_data_for_bulk_action')];
            return $notifications;
        }
        
        if (!empty($payoutIds)) {
            foreach ($payoutIds as $payoutId) {
                $payout = $this->get_seller_payout(['id' => $payoutId]);
                if (!empty($payout)) {
                    $updated_payout = $this->update_seller_payout($payout, ['status' => $action]);
                    if ($updated_payout->getId()) {
                        $notifications[] = [
                            'type' => 'success',
                            'message' => $this->translate->trans(
                                'message.payout_update_successfully',
                                [
                                    'payout_id' => $updated_payout->getId(),
                                ]
                            ),
                        ];
                    }
                } else {
                    $notifications[] = ['type' => 'danger', 'message' => 'Payout does not exists'];
                }
            }
        }
        return $notifications;
    }

    public function get_seller_settings($companyApplication, $seller, $fields = [])
    {
        $params = [
            'companyApplication' => $companyApplication,
            'seller' => $seller,
            'area' => 'wixmp-seller',
            'fields' => $fields
        ];
        $settingRepo = $this->entityManager->getRepository(Setting::class);

        return $settingRepo->getSetting($params);
    }

    public function update_seller_settings($setting, $params, $helper)
    {
        $commonHelper = $helper->getHelper('common');
        $em = $this->entityManager;
        // update fields

        // update_setting
        $em->persist($setting);
        $em->flush();

        return $setting;
    }

    public function getSellersAsOption($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $seller_repo = $this->entityManager->getRepository(Seller::class);
        $seller_list = $seller_repo->getSellersAsOptions($params);
        
        return array($seller_list, $params);
    }

    public function archieveSellers($request, $companyApplication, $sellerIds = [], $extraParams = [])
    {
        $params = $request->request->all();
        $company = $companyApplication->getCompany();
        $success = true;
        try {
            $notifications = [];
            $em = $this->entityManager;

            if (!empty($sellerIds)) {
                foreach ($sellerIds as $sellerId) {
                    $seller = $this->get_seller(array('id' => $sellerId));
                    if (!empty($seller)) {
                        $response = $this->disableSellerProducts($seller, $companyApplication, $params, $extraParams);
                        $seller->setIsArchieved(1);
                    }
                }
            }
            $em->persist($seller);
            $em->flush();
        } catch (DBALException $e) {
            $success = false;
            $notifications[] = [
                'type' => 'danger',
                'message' => $this->container->get('translator')->trans(
                    'cannot_archieve_this_item_%seller_name',
                    array('seller_name' => $seller->getSeller())
                ),
            ];
        }

        return [$notifications, $success];
    }

    public function unArchieveSellers($companyApplication, $sellerIds = [])
    {
        $success = true;
        $wixCompanyHelper = $this->getAppHelper('wixmpCompany');
        try {
            $notifications = [];
            $em = $this->entityManager;
            if (!empty($sellerIds)) {
                foreach ($sellerIds as $sellerId) {
                    $company_validation = $wixCompanyHelper->getUnarchivedSellerValidCount($companyApplication);
                    if (!$company_validation['allow']['seller']) {
                        continue;
                    }
                    $seller = $this->get_seller(array('id' => $sellerId));
                    if (!empty($seller)) {
                        $seller->setIsArchieved(0);
                        $em->persist($seller);
                        $em->flush();
                    }
                }
            }
        } catch (DBALException $e) {
            $success = false;
            $notifications[] = [
                'type' => 'danger',
                'message' => $this->container->get('translator')->trans(
                    'cannot_archieve_this_item_%seller_name',
                    array('seller_name' => $seller->getSeller())
                ),
            ];
        }

        return [$notifications, $success];
    }

    public function disableSellerProducts($seller, $companyApplication, $params, $extraParams = []) 
    {
        $notifications = [];
        $is_success = false;
        $catalogHelper = $this->getAppHelper('catalog');
        $platformHelper = $this->getAppHelper('platform');
        $company = $companyApplication->getCompany();
        
        $params['page'] = isset($params['page']) ? $params['page'] : 1;
        $params['limit'] = isset($params['limit']) ? $params['limit'] : 1;

        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0
        
        $hasCacheProductData = $this->cache->hasItem('seller_products_data_'.$company->getId());
        //get cahce id
        $cacheProductData = $this->cache->getItem('seller_products_data_'.$company->getId());
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
            
        if ($start == 0) {
            //clear cache if exists to prevent duplicacy
            $this->cache->deleteitem('seller_products_data_'.$company->getId());
        }

        if ($requestApi) {

            $requestParams = [
                //'page' => $productsData['page'],
                'company' => $company,
                'seller' => $seller,
                'get_all_results' => 1,
                'statuses' => ["A"],
            ];

            if (isset($extraParams['isBulk'])) {
                unset($requestParams['page']);
            }
            
            //either product data is empty or less then required
            //request API : get product from MP
            list($products, $filters) = $catalogHelper->get_products(
                $requestParams
            );
            
            // if ($products->getTotalItemCount()) {
            //     $productsData['items'] = array_merge($productsData['items'], $products->getItems());
            //     ++$productsData['page'];
            //     $productsData['totalCount'] = $products->getTotalItemCount();
            //     //save to cache
                
            //     $cacheProductData->set($productsData);
            //     $isSaved = $this->cache->save($cacheProductData);
            // }
        }
        //$toProcessProducts = $productsData['items'];
        $toProcessProducts = $products;

        if (!empty($toProcessProducts)) {
            
            $data = [];
            $data['product']['visible'] = false;
            $pIds = [];
            $is_success = true;
            // product repo to disable mp products
            $product_repo = $this->entityManager->getRepository(Products::class);
            foreach ($toProcessProducts as $product) { // in case limit is more than 10 : BC only allow 10 products in batch update
                $platform_product_data = [];
                $pIds[] = $product->getId();
                $seller = $product->getSeller();
                $additionalData = [
                    'id' => $product->getProdId(),
                ];

                $platform_product_data = $data;
                
                if (!empty($platform_product_data)) {
                    // bulk update : only 10 at a time : BC Limitation
                    $response = $platformHelper->update_platform_product($product->getProdId(), $platform_product_data);
                    $response = (is_array($response) && isset($response[0]) ) ? json_decode($response[0]) : (object) array();
                    
                    if (isset($response->product) && isset($response->product->id)) {
                        // platform product successsfully disabled : perform action
                        $is_success = true;
                        $notifications['success'][] = "Product Disabled Successfully!";
                    } else {
                        $is_success = false;
                        
                        if (isset($response->message) && !empty($response->message)) {
                            $notifications['danger'][] = $response->message;
                        }
                    }
                } else {
                    $notifications[] = ['type' => 'danger', 'message' => $this->container->get('translator')->trans('message.invalid_data_for_bulk_action')];
                    $is_success = false;
                }
            }
            
            if (!empty($pIds)) {
                // disable MP products : from MP REPO
                $product_repo->bulkUpdate($company, array('status_to' => 'D'), $pIds);
                $this->entityManager->flush();
            }
        }

        return array(
            'totalCount' => $productsData['totalCount'],
            'items' => [],
            //'limit' => 1,
            'notifications' => $notifications,
        );
    }

    public function performBatchAction($request, $formData, $companyApplication, $seller = null)
    {
        $notifications = [];
        $action = $formData['batch_action'];
        $sellerIds = $request->request->get('seller_ids');
        $company = $companyApplication->getCompany();
        if (empty($sellerIds)) {
            $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.empty_data_for_bulk_action')];

            return $notifications;
        }
        
        switch ($action) {
            
            case 'archive':
                $extraParams = [
                    "isBulk" => TRUE
                ];
                $response = $this->archieveSellers($request, $companyApplication, $sellerIds, $extraParams);
            break;

            case 'unarchive':
                $response = $this->unArchieveSellers($companyApplication, $sellerIds);
            break;
        }

        return $response;
    }

    public function get_archived_seller_list($company, $status = null)
    {
        $_seller = [];
        $seller_repo = $this->entityManager->getRepository(Seller::class);
        $seller_params = array(
            'company' => $company->getId(),
            'isArchieved' => 1
        );
        if (!empty($status)) {
            $seller_params['status'] = $status;
        }
        $seller_list = $seller_repo->findBy($seller_params);
        foreach ($seller_list as $seller) {
            $_seller[$seller->getSeller()] = $seller->getId();
        }

        return $_seller;
    }

    public function getSellerFields()
    {
        $fields = [
            'id' => [
                'field_id' => 'id',
                'field_label' => 'id',
                'is_primary' => true,
            ],
            'seller_company_name' => [
                'field_id' => 'seller_company_name',
                'field_label' => 'seller_company_name',
                'is_primary' => true,
            ],
            'email' => [
                'field_id' => 'email',
                'field_label' => 'email',
            ],
            'address' => [
                'field_id' => 'address',
                'field_label' => 'address',
            ],
            'address2' => [
                'field_id' => 'address2',
                'field_label' => 'address2',
            ],
            'city' => [
                'field_id' => 'city',
                'field_label' => 'city',
            ],
            'state' => [
                'field_id' => 'state',
                'field_label' => 'state',
            ],
            'country' => [
                'field_id' => 'country',
                'field_label' => 'country',
            ],
            'zipcode' => [
                'field_id' => 'zipcode',
                'field_label' => 'zipcode',
            ],
            'phone' => [
                'field_id' => 'phone',
                'field_label' => 'phone',
            ],
            'plan' => [
                'field_id' => 'plan',
                'field_label' => 'plan',
            ],
            'price' => [
                'field_id' => 'price',
                'field_label' => 'price',
            ],
            'status' => [
                'field_id' => 'status',
                'field_label' => 'status',
            ]
        ];

        return $fields;
    }

    public function handle_export_req($request, $formData, $companyApplication)
    {
        $params = $request->request->all();
        $filterParams = $request->query->get('filterParams');
        if (empty($filterParams)) {
            $filterParams = [];
        }
        $notifications = [];
        $company = $companyApplication->getCompany();

        $primary_fields = $formData['primary_fields'] ?? [];
        $form_data = $formData['form_data'] ?? [];
        // csv options
        $delimiter = $form_data['delimiter'];
        $pattern = [
            'enclosure' => '"'
        ];

        $options = [
            'enclosure' => '"',
            'delimiter' => $form_data['delimiter'] ?? 'T',
            'filename' => $form_data['file_name'] ?? 'products_'.rand(),
            'output_type' => $form_data['output_type'] ?? 'D',
        ];

        $importExportHelper = $this->getAppHelper('import_export');
        $importExportHelper->setCompanyApplication($companyApplication);

        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (empty($params['limit'])) {
            $params['limit'] = 10;
        }
        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0
        
        $seller_fields = array_merge($primary_fields, $form_data['other_fields']);
        $cacheId = 'seller_export_data_'.$company->getId();

        if ($start == 0) {
            //clear cache if exists to prevent duplicacy
            $this->cache->deleteItem($company->getId().'_'.$options['filename']);
            $this->cache->deleteitem($cacheId);
        }

        $sellersData = [];
        $requestDB = true; 
        $sellersData = array(
            'totalCount' => 0,
            'items' => [],
            'page' => 1,
        );
        
        $filterParams['company'] = $company;

        // check if batch requested is not in cache : requestApi
        if ($requestDB /*|| (($batch <= $sellersData['totalCount']) && ($batch > count($sellersData['items'])))*/) {
            $filterSeller = [];
            $sellers = $this->get_all_sellers($filterParams);
            if (!empty($sellers)) {
                $toProcessSellers = array_slice($sellers, $start, $params['limit']);
                $sellersData['totalCount'] = count($sellers);
                foreach ($toProcessSellers as $seller) {
                    $filterSeller[] = $this->arrangeCSVSeller($seller, $seller_fields);
                }
            }else{
                $notifications[] = [
                    'message' => 'Nothing to Import', // $this->_trans(''),
                    'type' => 'danger',
                ];

                return array(
                    'totalCount' => 0,
                    'items' => [],
                    'notifications' => $notifications,
                );
            }
        }
        
        $csv_data = $importExportHelper->fn_export($pattern, $filterSeller, $options);
        
        $notifications[] = [
            'message' => 'Export success',
            'type' => 'success',
        ];

        return array(
            'totalCount' => $sellersData['totalCount'],
            'items' => [],
            'notifications' => $notifications,
        );
    }

    public function arrangeCSVSeller($seller, $seller_fields)
    {
        if (empty($seller) || empty($seller_fields)) {
            return false;
        }

        $returnSeller = [];
        $ignore_fields = [
            //'id',
        ];
        
        foreach ($seller_fields as $field_name) {
            // check for ignore fields
            if (in_array($field_name, $ignore_fields)) {
                continue;
            }
            
            switch($field_name)
            {
                case "id":
                    $returnSeller['id'] = $seller->getId();
                    break;

                case "name":
                    $returnSeller['name'] = $seller->getSeller();
                    break;
                
                case "email":
                    $returnSeller['email'] = $seller->getEmail();
                    break;
                
                case "address":
                    $returnSeller['address'] = $seller->getAddress();
                    break;
                
                case "address2":
                    $returnSeller['address2'] = $seller->getAddress2();
                    break;
                
                case "city":
                    $returnSeller['city'] = $seller->getCity();
                    break;
                
                case "state":
                    $returnSeller['state'] = $seller->getState();
                    break;
                
                case "country":
                    $returnSeller['country'] = $seller->getCountry();
                    break;
                
                case "zipcode":
                    $returnSeller['zipcode'] = $seller->getZipcode();
                    break;

                case "phone":
                    $returnSeller['phone'] = $seller->getPhone();
                    break;
                
                case "plan":
                    $returnSeller['plan'] = $seller->getCurrentPlan()->getPlan();
                    break;

                case "price":
                    $returnSeller['price'] = $seller->getCurrentPlan()->getPrice();
                    break;
                
                case "status":

                    if ($seller->getStatus() == "A") {
                        $returnSeller['status'] = "Active";
                    } elseif ($seller->getStatus() == "D") {
                        $returnSeller['status'] = "Disable";
                    } elseif ($seller->getStatus() == "N") {
                        $returnSeller['status'] = "New";
                    } else {
                        $returnSeller['status'] = "NA";
                    }
                    
                    break;
            }

        }
        return $returnSeller;

    }
    
    // start subapp seller custom registration functions
    public function wix_get_custom_fields($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $field_repo = $this->entityManager->getRepository(CustomFeilds::class);
        $wix_custom_field_list = $field_repo->getWixCustomField($params);
        
        return array($wix_custom_field_list, $params);
    }

    public function wix_update_custom_filed($customField, $params = [])
    { 
        if (isset($params['company_application'])) {
            $customField->setCompanyApplication($params['company_application']);
        }

        if (isset($params['status'])) {
            $customField->setStatus($params['status']);
        }
        
        $customField->SetUpdatedAt(time());
        $em = $this->entityManager;
        $em->persist($customField);
        $em->flush();

        return $customField;
    }

    public function wix_get_custom_field($params = [])
    {   
        $custom_field_repo = $this->entityManager->getRepository(CustomFeilds::class);

        if (isset($params['find']) && $params['find'] == 'multiple') {
            unset($params['find']);
            $custom_field = $custom_field_repo->findBy($params);
        } else {
            $custom_field = $custom_field_repo->findOneBy($params);
        }
        
        return $custom_field;
    }

    public function wix_check_custom_filed_name($params)
    {
        $default_params = [
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        
        $params = array_merge($default_params, $params);

        $custom_field_repo = $this->entityManager->getRepository(CustomFeilds::class);
        $customField = $custom_field_repo->checkWixCustomFieldName($params);
        
        return $customField;
    }
    // end subapp seller custom registration functions

    public function get_wix_subapp_custom_field($companyApplication, $customFieldValueDatas = [])
    {
        $subCompanyApplication = $companyApplication->getSubCompanyApplications()->toArray();
        if(isset($subCompanyApplication) && !blank($subCompanyApplication) && $subCompanyApplication[0]->getStatus() == 'A') {
            $subCompanyApplication = $subCompanyApplication;
        } else {
            $subCompanyApplication = [];
        }

        $customFieldList = [];
        if(isset($subCompanyApplication) && !empty($subCompanyApplication) && isset($subCompanyApplication[0]) && !empty($subCompanyApplication[0])) {
            // We need to improve this concept
            $customFieldFlag = false;
            if(!empty($subCompanyApplication[0]->getSubscription()) && $subCompanyApplication[0]->getSubscription()->getStatus() == 'A' && $subCompanyApplication[0]->getSubscription()->getNextBillingDate() > time()) {
                $customFieldFlag = true;
            } elseif (!empty($subCompanyApplication[0]->getSubscription()) && $subCompanyApplication[0]->getSubscription()->getStatus() == 'A' && $subCompanyApplication[0]->getSubscription()->getNextBillingDate() > time() && $subCompanyApplication[0]->getSubscription()->getIsResubscribedAfterCancel() == true) {
                $customFieldFlag = true;
            } else {
                if (isset($customFieldValueDatas) && !empty($customFieldValueDatas)) {
                    $customFieldFlag = true;
                }
            }

            if($customFieldFlag) {
                $customFieldList = $this->wix_get_custom_field(['find' => 'multiple','status' => 'A', 'company_application' => $subCompanyApplication[0]]);
            }
        }

        return $customFieldList;
    }

    public function update_payout_commission($params = [])
    {
        $payoutCommission = new PayoutCommissions;

        if (isset($params['product'])) {
            $payoutCommission->setProduct($params['product']);
        } 
        if (isset($params['order'])) {
            $payoutCommission->setOrders($params['order']);
        }
        if (isset($params['commission_type'])) {
            $payoutCommission->setCommissionType($params['commission_type']);
        }
        if (isset($params['commission_rate'])) {
            $payoutCommission->setCommissionRate($params['commission_rate']);
        }
        if (isset($params['commission_amount'])) {
            $payoutCommission->setCommissionAmount($params['commission_amount']);
        }

        $em = $this->entityManager;
        $em->persist($payoutCommission);
        $em->flush();

        return $payoutCommission;
    }

    public function get_payout_commissions($params = [])
    {
        $payoutCommissionsRepo = $this->entityManager->getRepository(PayoutCommissions::class);
        $payoutCommissions = $payoutCommissionsRepo->findBy($params);

        return $payoutCommissions;
    }
    
    public function seller_stripe_config($company, $application, $helper) {
        $section = 'general';
        $commonHelper = $helper->getHelper('common');
        $general_setting_data = $commonHelper->get_section_settings($section, 'application', $application->getCode());
        $stripeConfig = $commonHelper->get_section_setting(['sectionName' => $section, 'company' => $company, 'application' => $application], true);
        if (isset($stripeConfig['stripe_payout_secret_key']) && $stripeConfig['stripe_payout_secret_key']->getValue() != "") {
            return $stripeConfig;
        } else{
            return false;
        }
    }

}