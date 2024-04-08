<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerShipment;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrderProducts;
use Webkul\Modules\Wix\WixmpBundle\Twig\AppRuntime;
use Webkul\Modules\Wix\WixmpBundle\Utils\PlatformHelper;

class SalesHelper extends WixMpBaseHelper
{
    public function get_order_status_list()
    {
        $order_status = [
            //'ORDER_PAID' => 'Paid',
            'ORDER_PLACED' => 'wix_wixmp_Unpaid',
            'CANCELED' => 'wix_wixmp_Canceled',
            //'NOT_FULFILLED' => 'Unfulfilled',
            //'FULFILLED' => 'Fulfilled',
            //'PARTIALLY_FULFILLED' => 'Partially Fulfilled',
            'ORDER_PAID' => 'wix_wixmp_Paid',
            'PAID' => 'wix_wixmp_Paid',
            'ORDER_FULFILLED' => 'wix_wixmp_Fulfilled',
            'ORDER_PAID' => 'wix_wixmp_Paid',
            'UNSPECIFIED_PAYMENT_STATUS' => 'wix_wixmp_NA',
            'NOT_PAID' => 'wix_wixmp_Not_Paid',
            'PARTIALLY_REFUNDED' => 'wix_wixmp_Partially_Refunded',
            'FULLY_REFUNDED' => 'wix_wixmp_Fully_Refunded',
            'PENDING' => 'wix_wixmp_Pending',
            'PARTIALLY_PAID' => 'wix_wixmp_Partially_Paid',
        ];

        return $order_status;
    }

    public function get_order_fullfillment_status_list()
    {
        $order_status = [
            'NOT_FULFILLED' => 'wix_wixmp_Unfulfilled',
            'FULFILLED' => 'wix_wixmp_Fulfilled',
            'CANCELED' => 'wix_wixmp_Canceled',
            'PARTIALLY_FULFILLED' => 'wix_wixmp_Partially_Fulfilled'
        ];

        return $order_status;
    }

    public function get_orders($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);

        $store_order_repo = $this->entityManager->getRepository(SellerOrders::class);

        $orders = $store_order_repo->getOrders($params);

        return array($orders, $params);
    }

    public function sync_order($request, $company_application)
    {   
        $params = $request->request->all();
        $company = $company_application->getCompany();
        $count = 0;
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (empty($params['limit'])) {
            $params['limit'] = 100;
        }

        $apiParams = [
            'query' => [
                'paging' => [
                    'limit' =>  $params['limit'],
                    'offset' => $params['page'] - 1,
                ]
            ]
        ];
        if ($params['page'] > 1) {
            $apiParams = [
                'query' => [
                    'paging' => [
                        'limit' => $params['limit'],
                        'offset'=> $params['page'] * $params['limit'],
                    ]
                ]
            ];
        }

        $orderIds = [];
        
        if (isset($params['orderIds']) && !empty($params['orderIds'])) {

            $orderIds = explode(',',$params['orderIds']);
            $this->cache->deleteitem('orders_data_'.$company->getId());

        } //else {

        //     if (isset($params['toDate']) && !empty($params['toDate'])) {
        //         $apiParams['max_date_created'] = $params['toDate'];
        //     }
    
        //     if (isset($params['fromDate']) && !empty($params['fromDate'])) {
        //         $apiParams['min_date_created'] = $params['fromDate'];
        //     }
    
        //     if (isset($params['minOrderId']) && !empty($params['minOrderId'])) {
        //         $apiParams['min_id'] = $params['minOrderId'];
        //     }
    
        //     if (isset($params['maxOrderId']) && !empty($params['maxOrderId'])) {
        //         $apiParams['max_id'] = $params['maxOrderId'];
        //     }

        //     if ($apiParams['max_date_created'] == $apiParams['min_date_created']) {
                
        //         $apiParams['max_date_created'] = $apiParams['max_date_created'] . " 23:59";
        //         $apiParams['min_date_created'] = $apiParams['min_date_created'] . " 00:00";
        //     }
        // }
        
        $start = ((int) $params['page'] - 1) * (int) $params['limit']; //first page starts with 0

        $notifications = [];
        
        $platformHelper = $this->getAppHelper("platform");
        
        // check in cache
        $hasCacheOrderData = $this->cache->hasItem('orders_data_'.$company->getId());
        //get cahce id
        $cacheOrderData = $this->cache->getItem('orders_data_'.$company->getId());

        $ordersData = [];

        $orderResponse = [];
      
        if (!$hasCacheOrderData) {
            if (empty($orderIds)) {
                list($orderResponse,$error) = $platformHelper->get_platform_orders(
                    $apiParams
                );
                
                $orderResponse = json_decode($orderResponse);
             
                $ordersData = array(
                    'totalCount' => isset($orderResponse->totalResults) ? $orderResponse->totalResults : 0,
                    'items' => [],
                    'page' => 1,
                );
                
                if ($error) {
                    $notifications[] = array(
                        'type' => 'danger',
                        'message' => $this->translate->trans('message.order.unable_to_import_order'),
                    );
    
                    return array(
                        'totalCount' => 0,
                        'items' => [],
                        'notifications' => $notifications,
                    );
                }
            } else {
                $ordersData = array(
                    'totalCount' => count($orderIds),
                    'items' => [],
                    'page' => 1,
                );
            }
            
            // if ( (isset($orderResponse['status']) && $orderResponse['status'] == 200) || !empty($orderIds)) {
            //     $ordersData = array(
            //         'totalCount' => $orderCount,
            //         'items' => [],
            //         'page' => 1,
            //     );
            // } else {
            //     $notifications[] = array(
            //         'type' => 'danger',
            //         'message' => $this->translate->trans('message.order.unable_to_import_order'),
            //     );

            //     return array(
            //         'totalCount' => 0,
            //         'items' => [],
            //         'notifications' => $notifications,
            //     );
            // }
        } else {
            $ordersData = $cacheOrderData->get();
        }
        $batch = (int) $params['limit'] + (int) $start;
        
        if ($batch > $ordersData['totalCount']) {
            $batch = $ordersData['totalCount'];
        }
        if (($batch <= $ordersData['totalCount']) && ($batch > count($ordersData['items']))) {
            //either order data is empty or less then required
            //request API
        
            if (empty($orderIds)) {
                if (empty($orderResponse)) {
                    list($orderResponse,$error) = $platformHelper->get_platform_orders(
                        $apiParams
                    );
                    $orderResponse = json_decode($orderResponse);
                    $response = isset($orderResponse->orders) ? $orderResponse->orders : [];
                } else {
                    $response = isset($orderResponse->orders) ? $orderResponse->orders : [];
                }
            } else {
                $response = [];
                foreach($orderIds as $orderId) {
                    $apiParams = [
                        'query' => [
                            "filter" => json_encode([
                                'number' => $orderId
                            ])
                        ]
                    ];
                    list($orderResponse,$error) = $platformHelper->get_platform_orders(
                        $apiParams
                    );
                    $orderResponse = json_decode($orderResponse);
                    //$response[] = isset($orderResponse->orders[0]) ? $orderResponse->orders[0] : [];
                    if (isset($orderResponse->orders[0]) && !empty($orderResponse->orders[0])) {
                        $response[] = $orderResponse->orders[0];
                    } else {
                        $notifications[] = array(
                            'type' => 'danger',
                            'message' => $this->translate->trans('message.order.store_not_having_this_order_to_sync'),
                        );
                    }
                }
            }
            
            if (!empty($response) && isset($response[0]) && !empty($response[0])) {
                $ordersData['items'] = array_merge($ordersData['items'], $response);
                ++$ordersData['page'];
                //save to cache
                $cacheOrderData->set($ordersData);
                $isSaved = $this->cache->save($cacheOrderData);
            } else {
                // $notifications[] = array(
                //     'type' => 'danger',
                //     'message' => $this->translate->trans('message.order.store_not_having_any_new_order_to_import'),
                // );
            }
        }
        
        // process batch
        $toProcessOrders = array_slice($ordersData['items'], $start, $params['limit']);
        if (!empty($toProcessOrders)) {
            foreach ($toProcessOrders as $order_data) { 
                if (!empty($order_data) && isset($order_data->archived) && $order_data->archived == false) {
                    $orderNumber = isset($order_data->number) ? $order_data->number : ""; 
                    $is_exist = $this->get_order(['storeOrderNo' => $orderNumber, 'company' => $company->getId()]); 
                    if (!$is_exist) {
                        $order_product = $this->get_order_products($company_application, $order_data); 
                        if (!empty($order_product['seller'])) { 
                            if (count($order_product['seller']) == 1) {
                                foreach ($order_product['seller'] as $seller => $seller_order_product) {
                                    $new_order = $this->create_order($order_data, $order_product, ['seller' => $seller, 'company_application' => $company_application, 'is_parent' => 'N']);
                                    if ($new_order->getId()) {
                                        ++$count;
                                    }
                                }
                            } else {
                                $parent_product = $this->create_order($order_data, $order_product, ['company_application' => $company_application, 'is_parent' => 'Y']);
                                foreach ($order_product['seller'] as $seller => $seller_order_product) {
                                    $new_order = $this->create_order($order_data, $order_product, ['seller' => $seller, 'company_application' => $company_application, 'is_parent' => 'N', 'parent_order' => $parent_product]);
                                    if ($new_order->getId()) {
                                        ++$count;
                                    }
                                }
                            }
                        }
                    } else {
                        $notifications[] = array(
                            'type' => 'danger',
                            'message' => $this->translate->trans('message.order.already_exist', ['count' => $count]),
                        );
                    }
                }
            }
        }
        if ($count) {
            $notifications[] = array(
                'type' => 'success',
                'message' => $this->translate->trans('message.order.order_imported_successfully', ['count' => $count]),
            );
        } elseif (empty($orderIds)) {
            $notifications[] = array(
                'type' => 'success',
                'message' => $this->translate->trans('message.order.store_not_having_any_new_order_to_import'),
            );
        }
        // clear cache on last batch
        if ($batch >= $ordersData['totalCount']) {
            $this->cache->deleteitem('orders_data_'.$company->getId());
        }
        
        return array(
            'totalCount' => $ordersData['totalCount'],
            'items' => $toProcessOrders,
            'notifications' => $notifications,
        );
    }

    public function get_order($params)
    {
        $store_order_repo = $this->entityManager->getRepository(SellerOrders::class);
        $order_data = $store_order_repo->findOneBy($params);

        return $order_data;
    }

    public function get_order_products($company_application, $order_data)
    {
        $format_order = array(
            'deleted' => [],
            'seller' => [],
        );
        $company = $company_application->getCompany();
        $product_repo = $this->entityManager->getRepository(Products::class);
        
        $response = isset($order_data->lineItems) ? $order_data->lineItems : [];
        if (!empty($response)) {
            foreach ($response as $product) {
                if (isset($product->lineItemType) && (strtolower($product->lineItemType) == 'physical' || strtolower($product->lineItemType) == 'digital')) {
                    $productId = isset($product->productId) ? $product->productId : "";
                    $product_details = $product_repo->getProductDetail($company, $productId);
                    // product is deleted or not synced
                    if ($product_details == false) {
                        $format_order['deleted'][$productId] = $product;
                    } elseif ($product_details->getSeller() == null) {
                        // notification product not synced or seller in not assigned
                    } else {
                        $format_order['seller'][$product_details->getSeller()->getId()]['products'][$productId] = $product; // Stored store order product
                        $format_order['seller'][$product_details->getSeller()->getId()]['seller_info'] = $product_details->getSeller();
                    }
                }
            }
        }
        
        return $format_order;
    }

    public function create_order($order_data, $order_product, $params)
    {   
        $company_application = $params['company_application'];
        //$bigCommerceV2Manual = new BigCommerceV2Manual($company_application->getApplication()->getClientId(), $company_application->getCompany()->getStoreHash(), $company_application->getAccessToken());
        //$orderTaxes = $bigCommerceV2Manual->getOrderTaxes($order_data->id);
        
        $orderShippingAdress = isset($order_data->shippingInfo) ? $order_data->shippingInfo : (object) array();
        
        $calcualted_order_data = $this->calcualte_order_content($order_product, $order_data,$orderShippingAdress);
        $total_seller = count($order_product['seller']);
        
        $error = false;
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        $company = $company_application->getCompany();
        $new_order = new SellerOrders();
        if (isset($params['seller'])) {
            $new_order->setSeller($order_product['seller'][$params['seller']]['seller_info']);
        }
        if (isset($params['parent_order'])) {
            $new_order->setIsParent($params['parent_order']);
        }
        
        if ($params['is_parent'] == 'N') {
            
            // divide complete order shipping to products : further assign to respective sellers.
            $orderShippingPerProduct = 0;

            $isTaxIncludedInProductPrice = isset($calcualted_order_data['taxIncludedInPrice']) ? $calcualted_order_data['taxIncludedInPrice'] : false;
            
            if (!empty($calcualted_order_data['total_shipping_products'])) {
                
                $orderShippingPerProduct = $calcualted_order_data['total_shipping'] / $calcualted_order_data['total_shipping_products'];
                
                //$shippingTaxPerProduct = $calcualted_order_data['seller_product'][$params['seller']]['shipping_tax'] / $calcualted_order_data['total_shipping_products'];

                $shippingTaxPerProduct = $calcualted_order_data['seller_product'][$params['seller']]['shipping_tax'];

                //$totalShippingPerProduct = ($orderShippingPerProduct * $calcualted_order_data['seller_product'][$params['seller']]['ship_product_count']) + ($shippingTaxPerProduct * $calcualted_order_data['seller_product'][$params['seller']]['ship_product_count']);

                $totalShippingPerProduct = ($orderShippingPerProduct * $calcualted_order_data['seller_product'][$params['seller']]['ship_product_count']) + $shippingTaxPerProduct;
            }
            
            if($orderShippingPerProduct != 0) {

                if ($isTaxIncludedInProductPrice) {

                    $new_order->setTotal( ($calcualted_order_data['seller_product'][$params['seller']]['subtotal'] -  $calcualted_order_data['seller_product'][$params['seller']]['discount'] ) + $totalShippingPerProduct);

                } else {

                    $new_order->setTotal( ($calcualted_order_data['seller_product'][$params['seller']]['subtotal'] -  $calcualted_order_data['seller_product'][$params['seller']]['discount'] ) + $calcualted_order_data['seller_product'][$params['seller']]['tax'] + $totalShippingPerProduct);
                }
            
            } else{

                if ($isTaxIncludedInProductPrice) {

                    $new_order->setTotal( 
                        ( ($calcualted_order_data['seller_product'][$params['seller']]['subtotal'] - $calcualted_order_data['seller_product'][$params['seller']]['discount'])) + $calcualted_order_data['seller_product'][$params['seller']]['shipping'] + $calcualted_order_data['seller_product'][$params['seller']]['shipping_tax']);

                } else {

                    $new_order->setTotal( 
                        ( ($calcualted_order_data['seller_product'][$params['seller']]['subtotal'] - $calcualted_order_data['seller_product'][$params['seller']]['discount']) + $calcualted_order_data['seller_product'][$params['seller']]['tax']) + $calcualted_order_data['seller_product'][$params['seller']]['shipping'] + $calcualted_order_data['seller_product'][$params['seller']]['shipping_tax']);

                }
            }
            
            if ($orderShippingPerProduct == 0) {
                
                $new_order->setShipping($calcualted_order_data['seller_product'][$params['seller']]['shipping']);  
                $new_order->setTax($calcualted_order_data['seller_product'][$params['seller']]['tax'] + $calcualted_order_data['seller_product'][$params['seller']]['shipping_tax']);

                /* fixed shipping applied on product */
            } else if($orderShippingPerProduct) {
                
                $new_order->setShipping($orderShippingPerProduct * $calcualted_order_data['seller_product'][$params['seller']]['ship_product_count']);

                //$new_order->setTax($calcualted_order_data['seller_product'][$params['seller']]['tax'] + ($shippingTaxPerProduct * $calcualted_order_data['seller_product'][$params['seller']]['ship_product_count'] ));

                $new_order->setTax($calcualted_order_data['seller_product'][$params['seller']]['tax'] + $shippingTaxPerProduct);

            }else{
                $new_order->setShipping(0.00);
            }

            if ($isTaxIncludedInProductPrice) {
                $new_order->setSubTotal($calcualted_order_data['seller_product'][$params['seller']]['subtotalExcludingTax']);
            } else {
                $new_order->setSubTotal($calcualted_order_data['seller_product'][$params['seller']]['subtotal']);
            }
            
            $new_order->setDiscount($calcualted_order_data['seller_product'][$params['seller']]['discount']);
        
        } else {
            $orderTotal = isset($order_data->totals->total) ? $order_data->totals->total : 0;
            $new_order->setTotal($orderTotal);
            $new_order->setShipping(0.00);
            $new_order->setSubTotal(0.00);
            $new_order->setTax(0.00);
            $new_order->setDiscount(0);
        }
        $customerName = (isset($order_data->billingInfo->address->fullName->firstName) && isset($order_data->billingInfo->address->fullName->lastName)) ? $order_data->billingInfo->address->fullName->firstName. " " .$order_data->billingInfo->address->fullName->lastName : "";
        
        $orderNumber = isset($order_data->number) ? $order_data->number : 0;
        $customerId = isset($order_data->buyerInfo->id) ? $order_data->buyerInfo->id : "";
        $createdDate = isset($order_data->dateCreated) ? $order_data->dateCreated : "";
        $orderId = isset($order_data->id) ? $order_data->id : "";

        $orderStatus = "";
        if (isset($order_data->activities)) {
            $statusCount = count($order_data->activities);
            $orderStatus = (isset($order_data->activities[$statusCount - 1]) && isset($order_data->activities[$statusCount - 1]->type)) ? $order_data->activities[$statusCount - 1]->type : "";
            
        }
        
        $fullfillmentStatus = isset($order_data->fulfillmentStatus) ? $order_data->fulfillmentStatus : "";

        $new_order->setCustomerName($customerName);
        $new_order->setStoreOrderNo($orderNumber);
        $new_order->setStoreOrderId($orderId);
        $new_order->setCompany($company);
        $new_order->setIsParent($params['is_parent']);
        $new_order->setCustomerId($customerId);
        $new_order->setCreatedAt(strtotime($createdDate));
        $new_order->setStatus($orderStatus);
        $new_order->setSellerStatus($orderStatus);
        $new_order->setFullfillmentStatus($fullfillmentStatus);
       
        $this->entityManager->persist($new_order);
        $this->entityManager->flush();
        $productDetails = [];
        if ($new_order->getId()) {
            if ($params['is_parent'] == 'N') {
                foreach ($order_product['seller'][$params['seller']]['products'] as $product_cat_id => $product_data) {
                    $productId = isset($product_data->productId) ? $product_data->productId : "";
                    $product_repo = $this->entityManager->getRepository(Products::class);
                    $product_details = $product_repo->findOneBy(['_prod_id' => $productId, 'company' => $company->getId()]);
                    
                    $product_details->setQuantity(isset($product_data->quantity) ? $product_data->quantity : 0);
                    $productDetails[] = $product_details;
                    
                    $new_order_products = new SellerOrderProducts();
                    $new_order_products->setCartProductId($productId); 
                    $new_order_products->setPlatformProductId($productId);
                    $new_order_products->setProduct($product_details);
                    $new_order_products->setSellerOrder($new_order);
                    $this->entityManager->persist($new_order_products);
                    $this->entityManager->flush();
                    
                    if (!$new_order_products->getId()) {
                        $error = true;
                        break;
                    }
                }
            }
        } else {
            $error = false;
        }
        if ($error) {
            $connection->rollback();
        } else {
            if ($params['is_parent'] == 'N') {
                $sellerHelper = $this->getAppHelper('seller');
                $payout = $sellerHelper->create_order_commission($new_order, $productDetails);
                // commit db before events
                $connection->commit();
                //trigger events
                $SellerEvent = new SellerEvent($company_application, $new_order->getSeller());
                $SellerEvent->setPayout($payout);
                // create order related data for email
                // $orderReference = [
                //     $new_order,
                //     $order_product['seller'][$params['seller']]['products'],
                //     $orderShippingAdress,
                //     $order_data
                // ];
                // $orderRefrence = $this->get_order_info(['id' => $new_order->getId()]);

                $SellerEvent->setWixOrder($order_data);
                $SellerEvent->setShippingAddress($orderShippingAdress);
                $SellerEvent->setOrderProducts($order_product['seller'][$params['seller']]['products']);
                $SellerEvent->setOrder($new_order);
                $this->dispatcher->dispatch(
                    $SellerEvent, SellerEvent::WIX_SELLER_ORDER_CREATE
                );
            } else {
                $connection->commit();
            }
        }
        
        return $new_order;
    }

    public function calcualte_order_content($order_product, $order_data, $orderShippingAdress = [])
    {
        $total_calculation = array(
            'seller_product' => array(),
            'total_shipping' => 0.00,
            'total_shipping_products' => 0,
            'total_tax' => 0.00,
            'taxIncludedInPrice' => false
        );

        foreach ($order_product['seller'] as $seller => $seller_product) {
            $total_calculation['seller_product'][$seller] = array(
                'total' => 0.00,
                'discount' => 0.00,
                'shipping' => 0.00,
                'ship_product_count' => 0,
                'tax' => 0.00,
                'subtotal' => 0.00,
                'shipping_tax' => 0.00,
                'subtotalExcludingTax' => 0.00,
            );

            foreach ($seller_product['products'] as $cart_product_id => $cart_product) {

                $productPrice = isset($cart_product->price) ? $cart_product->price : 0;
                $productTax = isset($cart_product->tax) ? $cart_product->tax : 0;
                $productDiscount = isset($cart_product->discount) ? $cart_product->discount : 0;

                $productPriceExTax = ($productPrice * $cart_product->quantity) - $productTax;

                $total_calculation['seller_product'][$seller]['total'] += $productPrice;

                $total_calculation['seller_product'][$seller]['tax'] += $productTax;
                $total_calculation['seller_product'][$seller]['subtotal'] += $productPrice * $cart_product->quantity;
                $total_calculation['seller_product'][$seller]['ship_product_count'] += $cart_product->quantity;
                $total_calculation['seller_product'][$seller]['discount'] += $productDiscount;

                $total_calculation['seller_product'][$seller]['subtotalExcludingTax'] += $productPriceExTax;

                # NOTE: This is common setting for all products.
                $total_calculation['taxIncludedInPrice'] = isset($cart_product->taxIncludedInPrice) ? $cart_product->taxIncludedInPrice : $total_calculation['taxIncludedInPrice'];
            }
        }
        
        $shippingPrice = isset($orderShippingAdress->shipmentDetails->priceData->price) ? $orderShippingAdress->shipmentDetails->priceData->price : 0;
        
        $shippingTax = isset($orderShippingAdress->shipmentDetails->tax) ? $orderShippingAdress->shipmentDetails->tax : 0;

        $total_calculation['seller_product'][$seller]['shipping'] += $shippingPrice;
        $total_calculation['seller_product'][$seller]['shipping_tax'] = $shippingTax;

        $total_calculation['total_shipping'] = $shippingPrice;
        
        $total_qty = isset($order_data->totals->quantity) ? $order_data->totals->quantity : 1;
        $total_calculation['total_shipping_products'] = $total_qty;
        
        return $total_calculation;
    }

    public function get_order_view_control($order, $company)
    {   
        $error = "";
        $sellerHelper = $this->getAppHelper('seller');
        list($order_response,$error) = $this->getOrderAPI('order', $order->getStoreOrderId());
        if (empty($error)) {
            $shipping_response = isset($order_response->order->shippingInfo) ? $order_response->order->shippingInfo : (Object) array();
            $platform_order_product = isset($order_response->order->lineItems) ? $order_response->order->lineItems : [];
            
            $payout_data = $sellerHelper->get_seller_payout(['orderId' => $order->getId()]);

            $payoutCommissions = $sellerHelper->get_payout_commissions(['orders' => $order]);
        
            $product_repo = $this->entityManager->getRepository(Products::class);
            $order_product = [];
            foreach ($order->getOrderProduct() as $product) {
                $order_product[$product->getCartProductId()] = [
                    'cart_id' => $product->getCartProductId(),
                    'platform_product_id' => $product->getPlatformProductId(),
                    'product_id' => $product->getProduct()->getId(),
                    'is_exists' => $product_repo->isProductExists($company, $product->getProduct()->getProdId()),
                ];
            }

            return [$order_response, $order_product, $platform_order_product, $shipping_response, $error, $payout_data, $payoutCommissions];
        } else {
            return [ [], [], [], [], $error];
        }
        
    }

    public function getOrderAPI($cache_id, $storeOrderId)
    {
        $platformHelper = $this->getAppHelper('platform');
        list($response,$error) = $platformHelper->get_platform_order_info($storeOrderId);
        return [json_decode($response), $error];
    }

    public function create_bc_order($item_id, $company_application, $eventData = null, PlatformHelper $platformHelper)
    {
        $company = $company_application->getCompany();
        $is_exist = $this->get_order(['storeOrderId' => $item_id, 'company' => $company->getId()]);
        // if status update do not create order
        
        if ((!empty($eventData) && isset($eventData->status)) && (isset($eventData->status->new_status_id)) && !$is_exist) {
            return false;
        }
        // temp 
        $testingOrder = false;
        
        if (!$is_exist || $testingOrder) {
            
            $response = $platformHelper->get_platform_order_info($item_id);
            $response = isset($response[0]) ? $response[0] : $response;

            $response = json_decode($response);

            if (isset($response->order)) {
                $order_data = $response->order;
                $order_product = $this->get_order_products($company_application, $order_data);
                $count = 0;  
                // test webhook for order : debugging purpose
                if ($testingOrder) {                    
                    // $SellerEvent = new SellerEvent($company_application, $is_exist->getSeller());
                    // $SellerEvent->setPayout(null);

                    // $SellerEvent->setBcOrder($order_data);
                    // $SellerEvent->setShippingAddress(null);
                    // $SellerEvent->setOrderProducts($order_product['seller'][$is_exist->getSeller()->getId()]['products']);
                    // $SellerEvent->setOrder($is_exist);
                    // $this->dispatcher->dispatch($SellerEvent, SellerEvent::SELLER_ORDER_CREATE);
                    // dd("asdasdasd");
                }    
                if (!empty($order_product['seller'])) {
                    if (count($order_product['seller']) == 1) {
                        foreach ($order_product['seller'] as $seller => $seller_order_product) {
                            
                            $new_order = $this->create_order($order_data, $order_product, ['seller' => $seller, 'company_application' => $company_application, 'is_parent' => 'N']);
                            if ($new_order->getId()) {
                                ++$count;
                            }
                        }
                    } else {
                        $parent_product = $this->create_order($order_data, $order_product, ['company_application' => $company_application, 'is_parent' => 'Y']);
                        foreach ($order_product['seller'] as $seller => $seller_order_product) {
                            $new_order = $this->create_order($order_data, $order_product, ['seller' => $seller, 'company_application' => $company_application, 'is_parent' => 'N', 'parent_order' => $parent_product]);
                            if ($new_order->getId()) {
                                ++$count;
                            }
                        }
                    }
                }
            }
        } else {
            //get all MP orders related to this BC order
            list($mpOrders, $params) = $this->get_orders(['company' => $company, 'get_all_results' => true, 'store_order_id' => $item_id]);

            $response = $platformHelper->get_platform_order_info($item_id);
            $response = isset($response[0]) ? $response[0] : $response;
            $eventData = json_decode($response);
            
            if (!empty($mpOrders)) {
                
                $orderUpdated = 0;
                foreach ($mpOrders as $mpOrder) {
                    
                    $orderStatus = "";
                    // if (isset($eventData->order->activities)) {
                    //     $statusCount = count($eventData->order->activities);
                    //     $orderStatus = (isset($eventData->order->activities[$statusCount - 1]) && isset($eventData->order->activities[$statusCount - 1]->type)) ? $eventData->order->activities[$statusCount - 1]->type : "";
                        
                    // }
                    
                    // if (isset($eventData->order->fulfillmentStatus) && strtoupper($eventData->order->fulfillmentStatus) == 'CANCELED')
                    // {
                    //     $orderStatus = "CANCELED";
                    // }
                    
                    $fullfillmentStatus = isset($eventData->order->fulfillmentStatus) ? $eventData->order->fulfillmentStatus : ( isset($eventData->fulfillmentStatus) ? $eventData->fulfillmentStatus : "" );

                    $orderStatus = isset($eventData->order->paymentStatus) ? $eventData->order->paymentStatus : "ORDER_PLACED";
                    
                    //if ((!empty($eventData) && isset($orderStatus)) && ($orderStatus != $mpOrder->getStatus())) {
                        // update status
                        $mpOrder->setStatus($orderStatus);
                        $mpOrder->setSellerStatus($orderStatus);
                        $mpOrder->setFullfillmentStatus($fullfillmentStatus);
                        $mpOrder->setUpdatedAt(time());
                        $this->entityManager->persist($mpOrder);
                        $this->entityManager->flush();
                        ++$orderUpdated;
                        // dispatch event
                        $SellerEvent = new SellerEvent($company_application, $mpOrder->getSeller());
                        $SellerEvent->setOrder($mpOrder);
                        $this->dispatcher->dispatch(
                            $SellerEvent,
                            SellerEvent::WIX_SELLER_ORDER_STATUS_CHANGE
                        );
                    //}
                }
                // if ($orderUpdated) {
                //     $this->entityManager->flush();
                // }
            }
        }
    }

    public function generateReportData($request, $filters)
    {
        // initialize report data array
        $reportData = array(
            'summary' => array(
                'orders_count' => 0,
                'total_sales' => 0,
                'total_commission' => 0,
                'avg_daily_sales' => 0,
                'avg_monthly_sales' => 0,
                'total_shipping_fees' => 0,
                'total_tax' => 0,
                'total_coupon_discount' => 0,
            ),
            'monthly_sales_report' => [],
            'seller_sales_report' => [
                '0' => [
                    'seller_name' => 'no_seller_orders',
                    'seller_id' => 0,
                    'total_sales' => 0,
                    'total_commission' => 0,
                    'total_tax' => 0,
                    'total_shipping' => 0,
                ],
            ],
            'top_products_by_sales' => [],
        );
        
        $totalMonths = $this->dateDiff($filters['from_date'], $filters['to_date']);
        $totalMonths = $totalMonths <= 0 ? 1 : $totalMonths; // in case no months
        $totalDays = $this->dateDiff($filters['start_date'], $filters['end_date'], 'days');
        $ordersData = [];

        $monthlyOrderProcessed = [];
        if (empty($totalMonths)) {
            $totalMonths = 1;
        }
        if (empty($totalDays)) {
            $totalDays = 1;
        }
        $filters['get_all_results'] = true;
        
        list($orders, $ff) = $this->get_orders($filters);

        foreach ($orders as $order) {
            $storeOrderId = $order->getStoreOrderId();
            $orderSeller = $order->getSeller();
            $orderSellerId = null;
            $sellerCommission = 0;
            $sellerPlan = null;
            $ordersData[$storeOrderId] = []; // add to order proccessed
            if (!empty($orderSeller)) {
                $sellerPlan = $orderSeller->getCurrentPlan();
                $orderSellerId = $orderSeller->getId();
            }
            if (!empty($sellerPlan)) {
                $sellerConditions = $sellerPlan->getConditions();
                $sellerCommission = isset($sellerConditions['commission']) ? $sellerConditions['commission'] : 0;
            }
            $orderSale = $order->getTotal();
            $orderCommission = $orderSale * $sellerCommission / 100;
            $orderNetRevenue = $orderSale - $orderCommission;
            $orderTax = $order->getTax();
            $orderShipping = $order->getShipping();
            $orderDate = $order->getCreatedAt();
            $orderYear = date('Y', $orderDate);
            $orderMonth = date('m', $orderDate);
            $formatedOrderDate = date('M', $orderDate).','.$orderYear;

            // summary report
            $reportData['summary']['total_sales'] += $orderSale;
            $reportData['summary']['total_commission'] += $orderCommission;
            $reportData['summary']['total_tax'] += $orderTax;
            $reportData['summary']['total_shipping_fees'] += $orderShipping;

            // monthly summary report
            if (!isset($monthlyOrderProcessed[$orderMonth.$orderYear])) {
                $monthlyOrderProcessed[$orderMonth.$orderYear] = [];
            }
            $monthlyOrderProcessed[$orderMonth.$orderYear][$storeOrderId] = []; // add to order processed

            if (isset($reportData['monthly_sales_report'][$orderMonth.$orderYear])) {
                $reportData['monthly_sales_report'][$orderMonth.$orderYear]['total_sales'] += $orderSale;
                $reportData['monthly_sales_report'][$orderMonth.$orderYear]['total_commission'] += $orderCommission;
                $reportData['monthly_sales_report'][$orderMonth.$orderYear]['total_tax'] += $orderTax;
                $reportData['monthly_sales_report'][$orderMonth.$orderYear]['total_shipping'] += $orderShipping;
                $reportData['monthly_sales_report'][$orderMonth.$orderYear]['net_revenue'] += $orderNetRevenue;
            } else {
                $reportData['monthly_sales_report'][$orderMonth.$orderYear] = array(
                    'total_sales' => $orderSale,
                    'total_commission' => $orderCommission,
                    'net_revenue' => $orderNetRevenue,
                    'total_tax' => $orderTax,
                    'total_shipping' => $orderShipping,
                    'month' => $orderMonth,
                    'year' => $orderYear,
                    'formatted_month_year' => $formatedOrderDate,
                    'date' => $orderDate,
                );
            }
            // add monthly order
            $reportData['monthly_sales_report'][$orderMonth.$orderYear]['order_count'] = count($monthlyOrderProcessed[$orderMonth.$orderYear]);

            // seller sales report
            if (!empty($orderSellerId)) {
                if (!isset($reportData['seller_sales_report'][$orderSellerId])) {// initialize seller array
                    $reportData['seller_sales_report'][$orderSellerId] = array(
                        'seller_name' => $orderSeller->getSeller(),
                        'seller_id' => $orderSellerId,
                        'total_sales' => 0,
                        'total_commission' => 0,
                        'total_tax' => 0,
                        'total_shipping' => 0,
                    );
                }
                $reportData['seller_sales_report'][$orderSellerId]['total_sales'] += $orderSale;
                $reportData['seller_sales_report'][$orderSellerId]['total_commission'] += $orderCommission;
                $reportData['seller_sales_report'][$orderSellerId]['total_tax'] += $orderTax;
                $reportData['seller_sales_report'][$orderSellerId]['total_shipping'] += $orderShipping;
            } 
            // else {
            //     // no seller order
            //     $reportData['seller_sales_report']['0']['total_sales'] += $orderSale;
            //     $reportData['seller_sales_report']['0']['total_commission'] += $orderCommission;
            //     $reportData['seller_sales_report']['0']['total_tax'] += $orderTax;
            //     $reportData['seller_sales_report']['0']['total_shipping'] += $orderShipping;
            // }

            // top products by sales
            foreach ($order->getOrderProduct() as $orderProduct) {
                $product = $orderProduct->getProduct();
                if (!isset($reportData['top_products_by_sales'][$product->getId()])) {
                    $reportData['top_products_by_sales'][$product->getId()] = array(
                        'product_name' => $product->getName(),
                        'total_sales' => 0,
                        'total_orders' => 0,
                    );
                }
                $reportData['top_products_by_sales'][$product->getId()]['total_sales'] += $product->getPrice();
                ++$reportData['top_products_by_sales'][$product->getId()]['total_orders'];
            }
        }
        // unset no seller if no sales for admin
        if (empty($reportData['seller_sales_report']['0']['total_sales'])) {
            unset($reportData['seller_sales_report']['0']);
        }
        //  avg daily and monthly sales
        $reportData['summary']['avg_daily_sales'] = $reportData['summary']['total_sales'] / $totalDays;
        $reportData['summary']['avg_monthly_sales'] += $reportData['summary']['total_sales'] / $totalMonths;
        $reportData['summary']['orders_count'] = count($ordersData);
        // sort top products by sales

        usort($reportData['top_products_by_sales'], function ($a, $b) {
            return (float) $b['total_sales'] - (float) $a['total_sales'];
        });
        // sort seller by sales
        // usort($reportData['seller_sales_report'], function($a,$b) {
        //     return (float)$b['total_sales'] - (float)$a['total_sales'];
        // });
        
        return $reportData;
    }

    public function dateDiff($startDate, $endDate, $return = 'month')
    {
        $returnInterval = 0;
        if ($return == 'month') { // dates must be as date string
            // $begin = new \DateTime($startDate);
            // $end = new \DateTime($endDate);
            $begin = (new \DateTime($startDate))->modify('first day of this month');
            $end = (new \DateTime($endDate))->modify('first day of next month');
            // $interval = date_diff($end, $begin);
            //$end = $end->modify('+1 month');
            $interval = \DateInterval::createFromDateString('1 month');
            $period = new \DatePeriod($begin, $interval, $end);
            $returnInterval = iterator_count($period);
        } elseif ($return == 'days') { //days
            $diff = abs($endDate - $startDate); // dates must be in timestamp
            $returnInterval = round($diff / (60 * 60 * 24));
        } else { //years
            $diff = abs($date2 - $date1);  // dates must be in timestamp
            $returnInterval = floor($diff / (365 * 60 * 60 * 24));
        }

        return $returnInterval;
    }

    public function getOrdersByIds($orderIds = [])
    {
        $order_repo = $this->entityManager->getRepository(SellerOrders::class);
        $orders = $order_repo->getOrdersByIds($orderIds);
        
        $orderData = [];

        foreach ($orders as $order) {
            $orderData[$order->getId()] = $order;
        }

        return $orderData;
    }
}