<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

use Doctrine\DBAL\DBALException;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Entity\Setting;
use App\Entity\ApplicationPayoutPayments;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayoutTransactions;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayoutTransactionsMapping;
use App\Utils\Payment\PaypalPayout;

class CommissionHelper extends WixMpBaseHelper
{
    public function get_seller_commissions($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);
        $seller_repo = $this->entityManager->getRepository(Seller::class);
        $seller_list = $seller_repo->getSellersCommissionList($params);

        return array($seller_list, $params);
    }

    public function getSellerAccountingIds($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);
        $sellerPayoutRepo = $this->entityManager->getRepository(SellerPayout::class);
        $accountingIds = $sellerPayoutRepo->getSellersAccountingIds($params);
        $accountIdsBySeller = [];
        /**
         * TODO: instead of looping: try to fetch filter data from db or use some framework method
         */
        if (!empty($accountingIds)) {            
            foreach ($accountingIds as $accoutId){
                if (!isset($accountIdsBySeller[$accoutId['seller_id']])) {
                    $accountIdsBySeller[$accoutId['seller_id']] = [];
                }                    
                $accountIdsBySeller[$accoutId['seller_id']][] = $accoutId['id'];
                
            }
        }
        return array($accountIdsBySeller, $params);
    }

    public function get_payout_transactions($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);
        $sellerPayotTransactionRepo = $this->entityManager->getRepository(SellerPayoutTransactions::class);
        $transactions_list = $sellerPayotTransactionRepo->getSellersTransactionsList($params);

        return array($transactions_list, $params);
    }

    public function performBatchAction($request, $formData, $companyApplication, $seller = null)
    {
        $notifications = [];
        $action = $formData['batch_action'];
        $sellerIds = $request->request->get('seller_ids');
        $payoutAmount = $request->request->get('payoutAmount');
        $payoutIds = $request->request->get('payoutIds');
        $company = $companyApplication->getCompany();
        $currency_code = $company->getCurrencyCode();
        $currentDateString = date('dmY');
        if (empty($sellerIds)) {
            $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.empty_data_for_bulk_action')];

            return $notifications;
        }
        $sellerHelper = $this->getAppHelper('seller');
        
        // $payout= [
        //     'sender_batch_header' => [],
        //     'items' => []
        // ];
        $payouts = new \PayPal\Api\Payout();
        $payoutDataToStore = [];
        $index = 1;
        if ($request->request->get('payment_type') == "paypal" || $action == 'pay' || $action == 'paypal') {
            $order =  new SellerPayoutTransactions();
            $orderAmount = 0;
            // build payment data 
            foreach ($sellerIds as $sellerId) {
                // get seller
                $seller = $sellerHelper->get_seller(['id'=>$sellerId]);
             
                // check if paypal email exists 
                $sellerSettings = $sellerHelper->get_seller_settings($companyApplication, $sellerId, ['paypalPayoutEmail']);
                
                if (empty($sellerSettings['paypalPayoutEmail'])) {
                    $notifications[] = [
                        'type' => 'danger',
                        'message' => $this->translate->trans(
                            'message.seller.payout_email_not_set',
                            [
                                'seller_id' => $sellerId,
                            ]
                        ),
                    ];
                    continue;
                }
                if ((!isset($payoutAmount[$sellerId]) || !isset($payoutIds[$sellerId]))|| (empty($payoutAmount[$sellerId]) || empty($payoutIds[$sellerId]))) {
                    $notifications[] = [
                        'type' => 'danger',
                        'message' => $this->translate->trans(
                            'message.seller.payout_invalid_amount_or_payout_ids',
                            [
                                'seller_id' => $sellerId,
                            ]
                        ),
                    ];
                    continue;
                }
                // add seller amount to  total payout amount 
                $orderAmount+=$payoutAmount[$sellerId];
                // set seller amount
                $tempItem = new \PayPal\Api\PayoutItem();
                $tempItem->setRecipientType('Email')
                    ->setNote("Payouts from ".$company->getName()."(".$company->getDomain().")")
                    ->setRecipientType('EMAIL')
                    ->setReceiver($sellerSettings['paypalPayoutEmail'])
                    ->setSenderItemId($company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index)
                    ->setAmount(new \PayPal\Api\Currency('{
                        "value": "'.$payoutAmount[$sellerId].'",
                        "currency":"'.$currency_code.'"
                    }'));
                $payouts->addItem($tempItem);
                
                $tempItemToStore = [
                    "recipient_type" => "EMAIL",
                    "amount" => [
                        "value" => $payoutAmount[$sellerId],
                        "currency" => $currency_code
                    ],
                    "receiver" => $sellerSettings['paypalPayoutEmail'],
                    "note" => "Payouts from ".$company->getName()."(".$company->getDomain().")",
                    "sender_item_id" => $company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index
                ];
               
                //$payout['items'][] = $tempItem;
                $payoutDataToStore[$sellerId] = [
                    'account_ids' => $payoutIds[$sellerId],
                    'payout'      => $tempItemToStore
                ];

                // create mapping data 
                $mappingData = new SellerPayoutTransactionsMapping();
                $mappingData->setSeller($seller);
                $mappingData->setCompany($company);
                $mappingData->setAmount((float)$payoutAmount[$sellerId]);
                $mappingData->setStatus('OPEN');
                $mappingData->setExtra([
                    'account_ids' => $payoutIds[$sellerId],
                    'payout'      => $tempItemToStore
                ]);
                $mappingData->setSenderItemId($company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index);
                $order->addSellerPayoutTransactionsMapping($mappingData);
                $index++;
            }
            $order->setAmount($orderAmount);
            // if any payout to create
            if (!empty($payoutDataToStore)) {
                // create transaction
                $notifications = $this->processPayout($payouts,$payoutDataToStore,$companyApplication,$order);
            }
        }
        return $notifications;
    }

    public function processPayout($payouts,$payoutDataToStore,$companyApplication,$order = null)
    {
        
        $notifications = [];
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        
        // paypal payout settings 
        $commonHelper = $this->getHelper('common');

        $mpGeneralSettings = $commonHelper->get_section_setting(['sectionName' => 'general', 'company' => $company, 'application' => $application], true);
        
        if (empty($mpGeneralSettings)) {
            $notifications[] = 
            [
                'type' => 'danger', 
                'message' => $this->translate->trans('message.payout.empty_credentials')
            ];
            return $notifications;
        }
        $paypalClientId = $mpGeneralSettings['paypal_payout_client_id']->getValue();
        $paypalSecret = $mpGeneralSettings['paypal_payout_secret_key']->getValue();
        $paypalMode = $mpGeneralSettings['paypal_payout_mode']->getValue();
        // check for missing credentials
        if (empty($paypalClientId) || empty($paypalSecret) || empty($paypalMode) ) {
            $notifications[] = 
            [
                'type' => 'danger', 
                'message' => $this->translate->trans('message.payout.empty_credentials')
            ];
            return $notifications;
        }

        $senderBatchHeader = new \PayPal\Api\PayoutSenderBatchHeader();
        // payment id
        $payoutPaymentRepo = $this->entityManager->getRepository(ApplicationPayoutPayments::class);
        $payment = $payoutPaymentRepo->findOneBy(['application'=>$application,'code'=>'paypal']);
        if (empty($payment)) {
            $notifications[] = 
            [
                'type' => 'danger', 
                'message' => $this->translate->trans('message.payout.no_payment_method')
            ];
            return $notifications;
        }
        // create transaction
        $order = $this->updateOrder(
            $order,
            [
                'status' => 'OPEN',
                'payment' => $payment,
                'company' => $company,
                'extra'   => json_encode($payoutDataToStore)
            ]
        );
        if (!empty($order) && !empty($order->getId())) {
            // payout header set            
            $senderBatchId = empty($order->getSenderBatchId())?$company->getStoreHash().'-'.$order->getId():$order->getSenderBatchId();
            $senderBatchHeader
                ->setSenderBatchId($senderBatchId)
                ->setEmailSubject("You have a payment");
            $payouts->setSenderBatchHeader($senderBatchHeader);

            $paypalPayout = new PaypalPayout();            
            // process
            $request = clone $payouts;
            $output = null;
            try {
                list($output,$error) = $paypalPayout->CreatePayout($companyApplication,$request, [
                    'client_id'  =>  $paypalClientId,
                    'client_secret' => $paypalSecret,
                    'mode'       => $paypalMode
                ]);
                if (empty($output) || empty($output->getBatchHeader())) {
                    if (!empty($error)) {
                        $notifications[] = 
                        [
                            'type' => 'danger', 
                            'message' => $error
                        ];
                    } else {
                        $notifications[] = 
                        [
                            'type' => 'danger', 
                            'message' => 'Unexpected Error Batch not process'
                        ];
                    }
                    return $notifications;
                }
            } catch (Exception $ex) {
                $notifications[] = 
                [
                    'type' => 'danger', 
                    'message' => $ex->getMessage()
                ];
                
            }            
            // transactions
            $order->setSenderBatchId($senderBatchId);
            $newAccountingStatus = null;
            if (!empty($output->getBatchHeader()->getPayoutBatchId())) {
                $order->setBatchId($output->getBatchHeader()->getPayoutBatchId());
                $order->setStatus($output->getBatchHeader()->getBatchStatus());
                if ($output->getBatchHeader()->getBatchStatus() == 'DENIED' || $output->getBatchHeader()->getBatchStatus() == 'CANCELED') {
                    $newAccountingStatus = 'X';
                } elseif ($output->getBatchHeader()->getBatchStatus() == 'PENDING' || $output->getBatchHeader()->getBatchStatus() == 'PROCESSING') {
                    $newAccountingStatus = 'I';
                } elseif ($output->getBatchHeader()->getBatchStatus() == 'SUCCESS') {
                    $newAccountingStatus = 'C';
                }
                // update transactions mappings 
                foreach ($order->getSellerPayoutTransactionsMapping() as &$transactionMapping)
                {
                    $transactionMapping->setSenderBatchId($senderBatchId);
                    $transactionMapping->setBatchId($output->getBatchHeader()->getPayoutBatchId());
                    $transactionMapping->setStatus($output->getBatchHeader()->getBatchStatus());
                }
                // set notification
                $notifications[] = [
                    'type' => 'success',
                    'message' => $this->translate->trans(
                        'message.seller.payout_success',
                        [
                            'batch_id' => $output->getBatchHeader()->getPayoutBatchId(),
                            'status' =>  $output->getBatchHeader()->getBatchStatus()
                        ]
                    ),
                ];
            }
            $order = $this->updateOrder($order);
            
            // CHANGE ACCOUNTING STATUS            
            if (!empty($newAccountingStatus)) {
                // change accounting status 
                $accountIds = call_user_func_array('array_merge',array_map(function($val){
                    return json_decode($val);
                },array_column($payoutDataToStore,'account_ids')));
                
                $sellerPayoutRepo = $this->entityManager->getRepository(SellerPayout::class);
                $sellerPayoutRepo->bulkUpdate($company, ['status'=>$newAccountingStatus], $accountIds);
            }
        } else {
            $notifications[] = 
            [
                'type' => 'danger', 
                'message' => $this->translate->trans('message.payout.create_fail')
            ];

        }
        return $notifications;
    }

    public function updateOrder($order = null, $data = [])
    {
        
        if (empty($order)) {
            $order = new SellerPayoutTransactions();
        }
        if (isset($data['batch_id']) && !empty($data['batch_id'])) {
            $order->setBatchId($data['batch_id']);
        }
        if (isset($data['payment']) && !empty($data['payment'])) {
            $order->setPayment($data['payment']);
        }
        if (isset($data['status']) && !empty($data['status'])) {
            $order->setStatus($data['status']);
        }
        if (isset($data['company']) && !empty($data['company'])) {
            $order->setCompany($data['company']);
        }
        if (isset($data['extra']) && !empty($data['extra'])) {
            $order->setExtra($data['extra']);
        }
        if (isset($data['transaction_id']) && !empty($data['transaction_id'])) {
            $order->setTransactionId($data['transaction_id']);
        }
        try {
            $this->entityManager->persist($order);
            $this->entityManager->flush();
        } catch (DBALException $e) {            
            // Only For Dev Mode
            if ($this->container->getParameter('kernel.environment') == 'dev') {
                $this->add_notification(
                    'danger', $e->getMessage()
                );
            }
            return false;
        }
        return $order;
    }

    public function syncPayout($batchId,$transaction,$companyApplication)
    {
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        // paypal payout settings 
        $commonHelper = $this->getHelper('common');

        $mpGeneralSettings = $commonHelper->get_section_setting(['sectionName' => 'general', 'company' => $company, 'application' => $application], true);
       
        if (empty($mpGeneralSettings)) {
            $this->add_notification('danger',$this->translate->trans('message.payout.empty_credentials'));
            return false;
        }
        $paypalClientId = $mpGeneralSettings['paypal_payout_client_id']->getValue();
        $paypalSecret = $mpGeneralSettings['paypal_payout_secret_key']->getValue();
        $paypalMode = $mpGeneralSettings['paypal_payout_mode']->getValue();
        // check for missing credentials
        if (empty($paypalClientId) || empty($paypalSecret) || empty($paypalMode) ) {
            $this->add_notification('danger',$this->translate->trans('message.payout.empty_credentials'));
            return false;
        }
        $paypalPayout = new PaypalPayout(); 
        try {
            list($payoutInfo,$error) = $paypalPayout->getPayoutDetails($batchId,[
                'client_id'  =>  $paypalClientId,
                'client_secret' => $paypalSecret,
                'mode'       => $paypalMode
            ]);
            if (empty($payoutInfo) || empty($payoutInfo->getBatchHeader())) {
                if (!empty($error)) {
                    $this->add_notification('danger',$error);
                } else {
                    $this->add_notification('danger','Unable in getting payout data');
                }
                return false;
            }
        } catch (Exception $ex) {
            $this->add_notification('danger',$ex->getMessage());
            return false;
        }   
        
        if (!empty($payoutInfo) && !empty($payoutInfo->getBatchHeader()->getPayoutBatchId())) {
            // update transaction status 
            $transaction->setStatus($payoutInfo->getBatchHeader()->getBatchStatus());
            
            $payoutItems = array_column($payoutInfo->getItems(),'payout_item');
            $senderIds = array_column($payoutItems,'sender_item_id');
            $associateveItemDetails = array_combine($senderIds,$payoutInfo->getItems());
            $accountingIds = [
                'X' => [],
                'C' => []
            ];
            // update all the items 
            foreach($transaction->getSellerPayoutTransactionsMapping() as &$transactionMap) {
                
                $payoutItemDetails = isset($associateveItemDetails[$transactionMap->getSenderItemId()])?$associateveItemDetails[$transactionMap->getSenderItemId()]:null;
                
                $extraData = $transactionMap->getExtra();
                $accountIds = json_decode($extraData['account_ids']);
                
                if (!empty($payoutItemDetails)) {
                    $transactionMap->setStatus($payoutItemDetails->getTransactionStatus());
                    $transactionMap->setPayoutItemId($payoutItemDetails->getPayoutItemId());
                    $transactionMap->setTransactionId($payoutItemDetails->getTransactionId());
                    // update accounting ids as per status 
                    if ($payoutItemDetails->getTransactionStatus() == 'SUCCESS') {
                        $accountingIds['C'] = array_merge($accountingIds['C'],$accountIds);
                    } elseif (in_array($payoutItemDetails->getTransactionStatus(),['FAILED','RETURNED','BLOCKED','REFUNDED','REVERSED'])) {
                        $accountingIds['X'] = array_merge($accountingIds['X'],$accountIds);
                    }
                }
            }
           
            // update order
            $this->updateOrder($transaction);
            //update accounting ids 
            $sellerPayoutRepo = $this->entityManager->getRepository(SellerPayout::class);
                
            foreach ($accountingIds as $status => $accountingStatusIds) {
                if (!empty($accountingStatusIds)) {
                    $sellerPayoutRepo->bulkUpdate($company, ['status'=>$status], $accountingStatusIds);
                }
            }
        } 
        return true;
    }

    public function get_payout_item_transactions($params)
    {
        $default_params = [
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'id',
            'order_by' => 'DESC',
        ];
        $params = array_merge($default_params, $params);
        $sellerPayotTransactionMappingRepo = $this->entityManager->getRepository(SellerPayoutTransactionsMapping::class);
        $transactions_item_list = $sellerPayotTransactionMappingRepo->getSellersTransactionsMappings($params);

        return array($transactions_item_list, $params);
    }

    public function processWebhook($payoutInfo, $companyApplication)
    {
        // create log
        $this->addPayoutLog($payoutInfo, $companyApplication);

        $company = $companyApplication->getCompany();
        $resourceType = $payoutInfo->resource_type;
        $resource = $payoutInfo->resource;
        if ($resourceType == 'payouts') {
            if (!empty($resource) && !empty($resource->batch_header->payout_batch_id)) {
                // fetch transaction
                $sellerPayoutTransactionRepo = $this->entityManager->getRepository(SellerPayoutTransactions::class);
                $transaction = $sellerPayoutTransactionRepo->getSellersTransactionsList([
                    'batch_id' => $resource->batch_header->payout_batch_id,
                    'company' =>  $company,
                    'sender_batch_id' => $resource->batch_header->sender_batch_header->sender_batch_id,
                    'get_single_result' => true

                ]);
                // update status 
                if (!empty($transaction) && $transaction->getStatus() != $resource->batch_header->batch_status) {
                    $transaction->setStatus($resource->batch_header->batch_status);
                    $this->updateOrder($transaction);
                }
            }
            
        } elseif ($resourceType == 'payouts_item') {
            $accountingIds = [
                'X' => [],
                'C' => []
            ];
            // fetch transaction maping
            if (!empty($resource) && !empty($resource->sender_batch_id)) {
                $sellerPayoutMappingRepo = $this->entityManager->getRepository(SellerPayoutTransactionsMapping::class);
               
                $transactionMap = $sellerPayoutMappingRepo->getSellersTransactionsMappings([
                    'batch_id' => $resource->payout_batch_id,
                    'sender_item_id' => $resource->payout_item->sender_item_id,
                    'company' =>  $company,
                    'sender_batch_id' => $resource->sender_batch_id,
                    'get_single_result' => true

                ]);
                if (empty($transactionMap)) {
                    // if no mapping found: may be occured if notify URL in paypal changed ex: same paypal used for different application and got notification for earlier app after change URL.
                    return ;
                }
                // update 
                $extraData = $transactionMap->getExtra();
                $accountIds = json_decode($extraData['account_ids']);
                
                if ($transactionMap->getStatus() != $resource->transaction_status) {
                    $transactionMap->setStatus($resource->transaction_status);
                    $transactionMap->setPayoutItemId($resource->payout_item_id);
                    $transactionMap->setTransactionId($resource->transaction_id);
                    // update accounting ids as per status 
                    if ($resource->transaction_status == 'SUCCESS') {
                        $accountingIds['C'] = array_merge($accountingIds['C'],$accountIds);
                    } elseif (in_array($resource->transaction_status,['FAILED','RETURNED','BLOCKED','REFUNDED','REVERSED'])) {
                        $accountingIds['X'] = array_merge($accountingIds['X'],$accountIds);
                    }
                }
                // update transaction mapping
                $this->entityManager->persist($transactionMap);
                $this->entityManager->flush();
                //update accounting ids 
                $sellerPayoutRepo = $this->entityManager->getRepository(SellerPayout::class);
                    
                foreach ($accountingIds as $status => $accountingStatusIds) {
                    if (!empty($accountingStatusIds)) {
                        $sellerPayoutRepo->bulkUpdate($company, ['status'=>$status], $accountingStatusIds);
                    }
                }
            }
        }
    }

    public function addPayoutLog($payoutInfo, $companyApplication)
    {
        $directory = $this->container->getParameter('kernel.project_dir')."/".$companyApplication->getApplication()->getAppPath();
        $company = $companyApplication->getCompany();
        
        if (!is_dir(__DIR__.'/paypal_webhook') && !file_exists('paypal_webhook/')) {
            mkdir('paypal_webhook');
        }

        if (!is_dir(__DIR__.'/paypal_webhook/'.$company->getStoreHash()) && !file_exists('paypal_webhook/'.$company->getStoreHash().'/')) {
            mkdir('paypal_webhook/'.$company->getStoreHash());
        }
        
        $date = date('d_M_Y', time());
        // Desired folder structure
        $structure = './paypal_webhook/'.$company->getStoreHash().'/'.$date;
        if (!is_dir(__DIR__.$structure) && !file_exists($structure.'/')) {
            mkdir($structure);
        }
        file_put_contents($structure.'/'.time().'_webhook_'.rand().'.json', json_encode($payoutInfo,JSON_PRETTY_PRINT));
    }

    public function processOrderAutoPay($event)
    {
     
        $seller = $event->getSeller();        
        $order = $event->getOrder();        
        if (empty($seller) || empty($order)) {
            return;
        }
        $companyApplication = $event->getCompanyApplication();
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        
        // get mp auto pay setting 
        $commonHelper = $this->getHelper('common');

        $mpGeneralSettings = $commonHelper->get_section_setting(['sectionName' => 'general', 'company' => $company, 'application' => $application], true);
        if ($company->getStoreHash() == "Giverb3de9") {
            if ($mpGeneralSettings['enable_auto_pay']->getValue() != $mpGeneralSettings['stripe_enable_auto_pay']->getValue()) {
                if ($mpGeneralSettings['stripe_enable_auto_pay']->getValue() == true) {
                    $this->stripeAutoPayment($event);
                    return;
                }
            }
        }
        if (empty($mpGeneralSettings)) {            
            return false;
        }
        // check if auto pay enable 
        if (!isset($mpGeneralSettings['enable_auto_pay']) || !$mpGeneralSettings['enable_auto_pay']->getValue()) {
            return;
        }
        // check if order status set for auto pay 
        if (!isset($mpGeneralSettings['auto_pay_order_status']) || !$mpGeneralSettings['auto_pay_order_status']->getValue()) {
            return;
        }
        $sellerId = $seller->getId();
        // process if order status match 
        if (($mpGeneralSettings['auto_pay_order_status']->getValue() == $order->getStatus()) || ($mpGeneralSettings['auto_pay_order_status']->getValue() == $order->getSellerStatus())) {
            // auto pay enable : process payout 
            // get payout
            $payout = $event->getPayout(); 
            if (empty($payout)) {
                $sellerPayoutRepo = $this->entityManager->getRepository(SellerPayout::class);
                $payout = $sellerPayoutRepo->findOneBy([
                    'company' => $company,
                    'payoutType' => 'O',
                    'order' => $order,
                    'seller' => $seller
                ]);
            }
            if (empty($payout)) {
                return;
            }
            $senderBatchId = $company->getStoreHash().'-'.$sellerId.'-'.$order->getId();
            
            $sellerPayoutTransactionRepo = $this->entityManager->getRepository(SellerPayoutTransactions::class);
            $transaction = $sellerPayoutTransactionRepo->getSellersTransactionsList([
                'company' =>  $company,
                'sender_batch_id' => $senderBatchId,
                'get_single_result' => true

            ]);
            
            if (!empty($transaction) && $transaction->getStatus() != 'OPEN') {
                // already proccessed
                return false;
            }
            
            $sellerIds = [$sellerId];
            $payoutAmount = [
                $sellerId => ($payout->getOrderAmount() - $payout->getCommissionAmount())
            ];
            $payoutIds = [
                $sellerId => json_encode([$payout->getId()])
            ];
            $company = $companyApplication->getCompany();
            $currency_code = $company->getCurrencyCode();
            $currentDateString = date('dmY');
            if (empty($sellerIds)) {
                return false;
            }
            $sellerHelper = $this->getAppHelper('seller');
            
            $payouts = new \PayPal\Api\Payout();
            $payoutDataToStore = [];
            $index = 1;
            
            if (empty($transaction)) {
                $transaction = new SellerPayoutTransactions();
            }
            
            // set sender batch id in case of auto order pay 
            $transaction->setSenderBatchId($senderBatchId);
            // build payment data 
            
            // check if paypal email exists 
            $sellerSettings = $sellerHelper->get_seller_settings($companyApplication, $sellerId, ['paypalPayoutEmail']);
            
            if (empty($sellerSettings['paypalPayoutEmail'])) {
                return;
            }
            
            if ((!isset($payoutAmount[$sellerId]) || !isset($payoutIds[$sellerId]))|| (empty($payoutAmount[$sellerId]) || empty($payoutIds[$sellerId]))) {
                return;
            }
            $transaction->setAmount($payoutAmount[$sellerId]);
            // set seller amount
            $tempItem = new \PayPal\Api\PayoutItem();
            $tempItem->setRecipientType('Email')
                ->setNote("Payouts from ".$company->getName()."(".$company->getDomain().")")
                ->setRecipientType('EMAIL')
                ->setReceiver($sellerSettings['paypalPayoutEmail'])
                ->setSenderItemId($company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index)
                ->setAmount(new \PayPal\Api\Currency('{
                    "value": "'.$payoutAmount[$sellerId].'",
                    "currency":"'.$currency_code.'"
                }'));
            $payouts->addItem($tempItem);
            
            $tempItemToStore = [
                "recipient_type" => "EMAIL",
                "amount" => [
                    "value" => $payoutAmount[$sellerId],
                    "currency" => $currency_code
                ],
                "receiver" => $sellerSettings['paypalPayoutEmail'],
                "note" => "Payouts from ".$company->getName()."(".$company->getDomain().")",
                "sender_item_id" => $company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index
            ];
            //$payout['items'][] = $tempItem;
            $payoutDataToStore[$sellerId] = [
                'account_ids' => $payoutIds[$sellerId],
                'payout'      => $tempItemToStore
            ];

            // create mapping data 
            $mappingData = new SellerPayoutTransactionsMapping();
            $mappingData->setSeller($seller);
            $mappingData->setCompany($company);
            $mappingData->setAmount((float)$payoutAmount[$sellerId]);
            $mappingData->setStatus('OPEN');
            $mappingData->setExtra([
                'account_ids' => $payoutIds[$sellerId],
                'payout'      => $tempItemToStore
            ]);
            $mappingData->setSenderItemId($company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index);
            $transaction->addSellerPayoutTransactionsMapping($mappingData);
            
            // create transaction
            $this->processPayout($payouts,$payoutDataToStore,$companyApplication,$transaction);
        }
    }

    public function performBatchActionStripe($request, $formData, $companyApplication, $seller = null)
    {
        $notifications = [];
        $action = $formData['batch_action'];
        $sellerIds = $request->request->get('seller_ids');
        $payoutAmount = $request->request->get('payoutAmount');
        $payoutIds = $request->request->get('payoutIds');
        $company = $companyApplication->getCompany();
        $currency_code = $company->getCurrencyCode();
        $currentDateString = date('dmY');
        if (empty($sellerIds)) {
            $notifications[] = ['type' => 'danger', 'message' => $this->translate->trans('message.empty_data_for_bulk_action')];

            return $notifications;
        }
        $sellerHelper = $this->getAppHelper('seller');
        
        $payoutDataToStore = [];
        $index = 1;
        if ($request->request->get('payment_type') == "stripe" || $action == 'stripe') {
            $order =  new SellerPayoutTransactions();
            $orderAmount = 0;
            // build payment data 
            foreach ($sellerIds as $sellerId) {
                // get seller
                $seller = $sellerHelper->get_seller(['id'=>$sellerId]);
             
                // check if stripe email and account Id exists 
                $sellerSettings = $sellerHelper->get_seller_settings($companyApplication, $sellerId, ['stripePayoutEmail','stripePayoutAccount']);
                if (empty($sellerSettings['stripePayoutEmail'])) {
                    $notifications[] = [
                        'type' => 'danger',
                        'message' => $this->translate->trans(
                            'message.seller.stripe_payout_not_set',
                            [
                                'seller_id' => $sellerId,
                            ]
                        ),
                    ];
                    continue;
                }
                if ((!isset($payoutAmount[$sellerId]) || !isset($payoutIds[$sellerId]))|| (empty($payoutAmount[$sellerId]) || empty($payoutIds[$sellerId]))) {
                    $notifications[] = [
                        'type' => 'danger',
                        'message' => $this->translate->trans(
                            'message.seller.payout_invalid_amount_or_payout_ids',
                            [
                                'seller_id' => $sellerId,
                            ]
                        ),
                    ];
                    continue;
                }
                // add seller amount to  total stripe amount 
                $orderAmount+=$payoutAmount[$sellerId];
                
                $tempItemToStore = [
                    "recipient_type" => "ACCOUNTID",
                    "amount" => [
                        "value" => $payoutAmount[$sellerId],
                        "currency" => $currency_code
                    ],
                    "receiver" => $sellerSettings['stripePayoutAccount'],
                    "note" => "Payouts from ".$company->getName()."(".$company->getDomain().")",
                    "sender_item_id" => $company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index,
                    "payment_method" => "stripe"
                ];
               
                $payoutDataToStore[$sellerId] = [
                    'account_ids' => $payoutIds[$sellerId],
                    'payout'      => $tempItemToStore
                ];

                // create mapping data 
                $mappingData = new SellerPayoutTransactionsMapping();
                $mappingData->setSeller($seller);
                $mappingData->setCompany($company);
                $mappingData->setAmount((float)$payoutAmount[$sellerId]);
                $mappingData->setStatus('OPEN');
                $mappingData->setExtra([
                    'account_ids' => $payoutIds[$sellerId],
                    'payout'      => $tempItemToStore
                ]);
                $mappingData->setSenderItemId($company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index);
                $order->addSellerPayoutTransactionsMapping($mappingData);
                $index++;
            }
            $order->setAmount($orderAmount);
            // if any payout to create
            if (!empty($payoutDataToStore)) {
                // create transaction
                $notifications = $this->stripeProcessPayout($payoutDataToStore,$companyApplication,$order);
            }
        }
        return $notifications;
    }

    public function stripeProcessPayout($payoutDataToStore,$companyApplication,$order = null)
    {
        
        $notifications = [];
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        
        // stripe payout settings 
        $commonHelper = $this->getHelper('common');
        $stripeHelper = $this->getHelper('StripeHelper');
        $mpGeneralSettings = $commonHelper->get_section_setting(['sectionName' => 'general', 'company' => $company, 'application' => $application], true);
        
        if (empty($mpGeneralSettings)) {
            $notifications[] = 
            [
                'type' => 'danger', 
                'message' => $this->translate->trans('message.payout.empty_credentials')
            ];
            return $notifications;
        }
        // Stripe Configure 
        $stripeConfig = [
            'api_client_key' => $mpGeneralSettings['stripe_payout_client_id']->getValue(),
            'api_secret_key' =>  $mpGeneralSettings['stripe_payout_secret_key']->getValue(),
            'api_mode' => $mpGeneralSettings['stripe_payout_mode']->getValue(),
        ];
        // check for missing credentials
        if (empty($stripeConfig['api_client_key']) || empty($stripeConfig['api_secret_key']) || empty($stripeConfig['api_mode']) ) {
            $this->add_notification('danger',$this->translate->trans('message.payout.empty_credentials'));
            return false;
        }
        // payment id
        $payoutPaymentRepo = $this->entityManager->getRepository(ApplicationPayoutPayments::class);
        $payment = $payoutPaymentRepo->findOneBy(['application'=>$application,'code'=>'stripe']);

        if (empty($payment)) {
            $notifications[] = 
            [
                'type' => 'danger', 
                'message' => $this->translate->trans('message.payout.no_payment_method')
            ];
            return $notifications;
        }
        // create transaction
        $order = $this->updateOrder(
            $order,
            [
                'status' => 'OPEN',
                'payment' => $payment,
                'company' => $company,
                'extra'   => json_encode($payoutDataToStore)
            ]
        );
        if (!empty($order) && !empty($order->getId())) {
            $senderBatchId = empty($order->getSenderBatchId())?$company->getStoreHash().'-'.$order->getId():$order->getSenderBatchId();
            $output = null; //receive stripe response data
            foreach ($payoutDataToStore as $payoutValue) {
                $payoutData = [
                    // Stripe allows integer value as price, if you want to charge $49.99 then multiply it with 100.
                    'amount' => $payoutValue['payout']['amount']['value'] * 100,
                    'currency' => $payoutValue['payout']['amount']['currency'],
                    'receiver' => $payoutValue['payout']['receiver'],
                    'note' => $payoutValue['payout']['note'],
                ];
            }
            $output = $stripeHelper->payoutStripeTransfer($stripeConfig, $payoutData);
            // transactions
            $order->setSenderBatchId($senderBatchId);
            $newAccountingStatus = null;
            if (!empty($output['data']) && $output['code'] == 200) {
                
                $order->setTransactionId($output['data']->balance_transaction);
                $order->setBatchId($output['data']->id);
                $order->setStatus("SUCCESS");
                $newAccountingStatus = 'C';
                // update transactions mappings 
                foreach ($order->getSellerPayoutTransactionsMapping() as &$transactionMapping)
                {
                    $transactionMapping->setSenderBatchId($senderBatchId);
                    $transactionMapping->setBatchId($output['data']->id);
                    $transactionMapping->setTransactionId($output['data']->id);
                    $transactionMapping->setStatus("SUCCESS");

                }
                // set notification
                $notifications[] = [
                    'type' => 'success',
                    'message' => $this->translate->trans(
                        'message.seller.payout_success',
                        [
                            'batch_id' => $output['data']->id,
                            'status' =>  200
                        ]
                    ),
                ];
            } else {
                $notifications[] = [
                    'type' => 'danger', 
                    'message' => $output['message']
                ];
            }
            $order = $this->updateOrder($order);
            
            // CHANGE ACCOUNTING STATUS            
            if (!empty($newAccountingStatus)) {
                // change accounting status 
                $accountIds = call_user_func_array('array_merge',array_map(function($val){
                    return json_decode($val);
                },array_column($payoutDataToStore,'account_ids')));
                
                $sellerPayoutRepo = $this->entityManager->getRepository(SellerPayout::class);
                $sellerPayoutRepo->bulkUpdate($company, ['status'=>$newAccountingStatus], $accountIds);
            }
        } else {
            $notifications[] = 
            [
                'type' => 'danger', 
                'message' => $this->translate->trans('message.payout.create_fail')
            ];

        }
        return $notifications;
    }

    public function syncStripePayout($batchId,$transaction,$companyApplication)
    {
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        // stripe payout settings 
        $commonHelper = $this->getHelper('common');
        $stripeHelper = $this->getHelper('StripeHelper');

        $mpGeneralSettings = $commonHelper->get_section_setting(['sectionName' => 'general', 'company' => $company, 'application' => $application], true);
       
        if (empty($mpGeneralSettings)) {
            $this->add_notification('danger',$this->translate->trans('message.payout.empty_credentials'));
            return false;
        }
        $stripeConfig = [
            'api_client_key' => $mpGeneralSettings['stripe_payout_client_id']->getValue(),
            'api_secret_key' =>  $mpGeneralSettings['stripe_payout_secret_key']->getValue(),
            'api_mode' => $mpGeneralSettings['stripe_payout_mode']->getValue(),
        ];
        // check for missing credentials
        if (empty($stripeConfig['api_client_key']) || empty($stripeConfig['api_secret_key']) || empty($stripeConfig['api_mode']) ) {
            $this->add_notification('danger',$this->translate->trans('message.payout.empty_credentials'));
            return false;
        }
        $payoutInfo = $stripeHelper->payoutStripeTransferUpdate($stripeConfig,$transaction);
        if (!empty($payoutInfo['data']) && !empty($payoutInfo['data']->id)) {
            // update transaction status 
            $transaction->setStatus('SUCCESS');
            $transaction->setUpdatedAt(time());
            $accountingIds = [
                'X' => [],
                'C' => []
            ];
            // update all the items 
            foreach($transaction->getSellerPayoutTransactionsMapping() as &$transactionMap) {
                
                $extraData = $transactionMap->getExtra();
                $accountIds = json_decode($extraData['account_ids']);

                $transactionMap->setBatchId($payoutInfo['data']->id);
                $transactionMap->setTransactionId($payoutInfo['data']->id);
                $transactionMap->setStatus(200);
                $accountingIds['C'] = array_merge($accountingIds['C'],$accountIds);

            }
           
            // update order
            $this->updateOrder($transaction);
            //update accounting ids 
            $sellerPayoutRepo = $this->entityManager->getRepository(SellerPayout::class);
                
            foreach ($accountingIds as $status => $accountingStatusIds) {
                if (!empty($accountingStatusIds)) {
                    $sellerPayoutRepo->bulkUpdate($company, ['status'=>$status], $accountingStatusIds);
                }
            }
        } else {
            $this->add_notification('danger',$this->translate->trans($payoutInfo['message']));
            return false;
        }
        return true;
    }

    public function stripeAutoPayment($event) {
        $seller = $event->getSeller();        
        $order = $event->getOrder();        
        if (empty($seller) || empty($order)) {
            return;
        }
        $companyApplication = $event->getCompanyApplication();
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        
        // get mp auto pay setting 
        $commonHelper = $this->getHelper('common');
        $mpGeneralSettings = $commonHelper->get_section_setting(['sectionName' => 'general', 'company' => $company, 'application' => $application], true);
        if (empty($mpGeneralSettings)) {            
            return false;
        }
        // check if auto pay enable 
        if (!isset($mpGeneralSettings['stripe_enable_auto_pay']) || !$mpGeneralSettings['stripe_enable_auto_pay']->getValue()) {
            return;
        }
        // check if order status set for auto pay 
        if (!isset($mpGeneralSettings['stripe_auto_pay_order_status']) || !$mpGeneralSettings['stripe_auto_pay_order_status']->getValue()) {
            return;
        }
        $sellerId = $seller->getId();
        // process if order status match 
        if (($mpGeneralSettings['stripe_auto_pay_order_status']->getValue() == $order->getStatus()) || ($mpGeneralSettings['stripe_auto_pay_order_status']->getValue() == $order->getSellerStatus())) {
            // auto pay enable : process payout 
            // get payout
            $payout = $event->getPayout(); 
            if (empty($payout)) {
                $sellerPayoutRepo = $this->entityManager->getRepository(SellerPayout::class);
                $payout = $sellerPayoutRepo->findOneBy([
                    'company' => $company,
                    'payoutType' => 'O',
                    'order' => $order,
                    'seller' => $seller
                ]);
            }
            if (empty($payout)) {
                return;
            }
            $senderBatchId = $company->getStoreHash().'-'.$sellerId.'-'.$order->getId();

            $sellerPayoutTransactionRepo = $this->entityManager->getRepository(SellerPayoutTransactions::class);
            $transaction = $sellerPayoutTransactionRepo->getSellersTransactionsList([
                'company' =>  $company,
                'sender_batch_id' => $senderBatchId,
                'get_single_result' => true
                
            ]);
            
            if (!empty($transaction) && $transaction->getStatus() != 'OPEN') {
                // already proccessed
                return false;
            }
            
            $sellerIds = [$sellerId];
            $payoutAmount = [
                $sellerId => ($payout->getOrderAmount() - $payout->getCommissionAmount())
            ];
            $payoutIds = [
                $sellerId => json_encode([$payout->getId()])
            ];
            $company = $companyApplication->getCompany();
            $currency_code = $company->getCurrencyCode();
            $currentDateString = date('dmY');
            if (empty($sellerIds)) {
                return false;
            }
            $sellerHelper = $this->getAppHelper('seller');
            $payoutDataToStore = [];
            $index = 1;
            
            if (empty($transaction)) {
                $transaction = new SellerPayoutTransactions();
            }
            
            // set sender batch id in case of auto order pay 
            $transaction->setSenderBatchId($senderBatchId);
            // build payment data 
            
            // check if email account exists 
            $sellerSettings = $sellerHelper->get_seller_settings($companyApplication, $sellerId, ['stripePayoutAccount']);
            
            if (empty($sellerSettings['stripePayoutAccount'])) {
                return;
            }
            
            if ((!isset($payoutAmount[$sellerId]) || !isset($payoutIds[$sellerId]))|| (empty($payoutAmount[$sellerId]) || empty($payoutIds[$sellerId]))) {
                return;
            }
            $transaction->setAmount($payoutAmount[$sellerId]);
            $tempItemToStore = [
                "recipient_type" => "ACCOUNTID",
                "amount" => [
                    "value" => $payoutAmount[$sellerId],
                    "currency" => $currency_code
                ],
                "receiver" => $sellerSettings['stripePayoutAccount'],
                "note" => "Payouts from ".$company->getName()."(".$company->getDomain().")",
                "sender_item_id" => $company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index,
                "payment_method" => "stripe"
            ];
            $payoutDataToStore[$sellerId] = [
                'account_ids' => $payoutIds[$sellerId],
                'payout'      => $tempItemToStore
            ];

            // create mapping data 
            $mappingData = new SellerPayoutTransactionsMapping();
            $mappingData->setSeller($seller);
            $mappingData->setCompany($company);
            $mappingData->setAmount((float)$payoutAmount[$sellerId]);
            $mappingData->setStatus('OPEN');
            $mappingData->setExtra([
                'account_ids' => $payoutIds[$sellerId],
                'payout'      => $tempItemToStore
            ]);
            $mappingData->setSenderItemId($company->getStoreHash()."-".$sellerId."-".$currentDateString."-".$index);
            $transaction->addSellerPayoutTransactionsMapping($mappingData);

            // create transaction
            $this->stripeProcessPayout($payoutDataToStore,$companyApplication,$transaction);
        }
    }

}