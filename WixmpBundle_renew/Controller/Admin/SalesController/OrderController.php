<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\SalesController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Webkul\Modules\Wix\WixmpBundle\Utils\SalesHelper;
use Webkul\Modules\Wix\WixmpBundle\Utils\PlatformHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\Modules\Wix\WixmpBundle\Form\Sales\OrderStatusType;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpKernel\KernelInterface;
use Webkul\Modules\Wix\WixmpBundle\Twig\AppRuntime;
use App\Helper\EmailHelper;
use Doctrine\ORM\EntityManagerInterface;
/**
 * @Route("/sales/order", name="wixmp_sales_order_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class OrderController extends BaseController
{
    public function __construct(TranslatorInterface $translator, WixMpBaseHelper $wixMpBaseHelper, EventDispatcherInterface $dispatcher,KernelInterface $kernel, Pdf $pdf,EntityManagerInterface $entityManager)
    {
        $this->translate = $translator;
        $this->wixMpBaseHelper = $wixMpBaseHelper;
        $this->dispatcher = $dispatcher;
        $this->kernel = $kernel;
        $this->pdf = $pdf;
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function order_manage(Request $request, CompanyApplication $companyApplication, PlatformHelper $platformHelper, SalesHelper $salesHelper)
    {
        $order_statuses = $salesHelper->get_order_status_list();
        // dd($order_statuses);
        $fullfillment_order_statuses = $salesHelper->get_order_fullfillment_status_list();
        // dd($fullfillment_order_statuses);
        $form = $this->createForm(OrderStatusType::class);
        // dd($form);
        $params = $request->query->all();
        $params['company'] = $company = $companyApplication->getCompany();
        // dd($params['company']);

        $params['parent_only'] = 'N';
        
        list($orders, $params) = $salesHelper->get_orders($params);
        // dd($orders);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
        }

        $planApplications = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
        // dd($planApplications);
        if($planApplications) {
            $planApplicationData = $planApplications;
        } else {
            $planApplicationData = [];
        }
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'sales/order_manage',
            'title' => 'orders',
            'list_count' => $orders->getTotalItemCount(),
            'orders' => $orders,
            'search' => $params,
            'order_statuses' => $order_statuses,
            'plan_application_data' => $planApplicationData,
            'wix_fullfillment_order_statuses' => $fullfillment_order_statuses,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/sync", name="sync")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function order_sync(Request $request, CompanyApplication $companyApplication, SalesHelper $salesHelper)
    {   
        $response = $salesHelper->sync_order($request, $companyApplication);
        // dd($response);
        return new JsonResponse($response);
    }
     /**
     * @Route("/{order_id}/view", name="view", methods="get")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("order", options={"mapping": {"order_id": "id"}})
     */
    public function order_view(Request $request, CompanyApplication $companyApplication, SellerOrders $order, PlatformHelper $platformHelper, SalesHelper $salesHelper)
    {
        $company = $companyApplication->getCompany();
        list($order_response, $order_product, $platform_order_product, $shipping_response, $error, $payout_data, $payoutCommissions) = $salesHelper->get_order_view_control($order, $company);
       
        $isPopup = $request->get('popup');

        $header = true;
        $menu = true;
        $breadcrumb = true;

        if ($isPopup) {
            $header = false;
            $menu = false;
            $breadcrumb = false;
        }
        if (empty($error)) {
            return $this->render('@wixmp_twig/view_templates/index.html.twig', [
                'header' => $header,
                'menu' => $menu,
                'breadcrums' => $breadcrumb,
                'template_name' => 'sales/order_view',
                'title' => 'order_details',
                'order_details' => $order,
                'store_order_info' => $order_response,
                'store_order_shipping_info' => $shipping_response,
                'platform_order_product' => $platform_order_product,
                'order_product' => $order_product,
                'product_details' => $order->getOrderProduct()->toArray(),
                'payout_data' => $payout_data,
                'payout_commissions' => $payoutCommissions
            ]);
        } else {
            return $this->redirectToRoute('wixmp_sales_order_manage', ['storeHash' => $company->getStoreHash()]);
        }
    }
    /**
     * @Route("/send_mail/{order_id}", name="send_mail", methods="get")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("order", options={"mapping": {"order_id": "id"}})
     */
    public function send_mail(Request $request, CompanyApplication $companyApplication, SellerOrders $order, PlatformHelper $platformHelper, SalesHelper $salesHelper,$order_id)
    {
        $company = $companyApplication->getCompany();
        list($order_response, $order_product, $platform_order_product, $shipping_response, $payout_data) = $salesHelper->get_order_view_control($order, $company);
        $shippingData = '';
        if(isset($shipping_response) && !empty($shipping_response)) {
            $shippingData = $shipping_response;
        }

        $bcOrderData = '';
        list($bcOrder,$param) = $platformHelper->get_platform_order_info($order->getStoreOrderId());
        $bcOrder = json_decode($bcOrder)->order;
        if(isset($bcOrder) && !empty($bcOrder)) {
            $bcOrderData = $bcOrder;
        }
        $email_helper = $this->wixMpBaseHelper->getHelper('email');
        $seller = $order->getSeller();
        $recipentEmail = $seller->getEmail();

        // dd($platform_order_product[0]);
        $appRunTime = new AppRuntime($this->wixMpBaseHelper->entityManager,$this->wixMpBaseHelper->container,$this->wixMpBaseHelper->logger);  
        
        $sellerAllowedCustDetails = $seller->getAllowedCustomerDetails();
        $allowedCustomerDetails = [];
        foreach($sellerAllowedCustDetails as $allowed){
            $allowedCustomerDetails[] = $customerDetailTree[$allowed];
        }
        // End Shipping Details
        if ($order->getStatus()) {
            $email_helper->send_mail(
                $recipentEmail,
                [],
                'seller/order_create',
                'application',
                array(
                    'company' => $companyApplication->getCompany(),
                    'seller' => $seller,
                    'payout' => $payout_data,
                    'order' => $order,
                    'product_list' => $platform_order_product,
                    'shipping_address' => $shippingData,
                    'bcOrder' =>  $bcOrderData,
                    'application' => $companyApplication->getApplication(),
                    'sellerAllowedCustomerDetail' => $allowedCustomerDetails,
                )
            );
            // if ($order->getStatus() == $params['status_to']) {
                $this->addFlash('success', $this->translate->trans('message.common.mail_sent_successfully'));
            // } else {
                // $this->addFlash('danger', $this->translate->trans('message.common.unable_to_change_status'));
            // }
            return $this->redirectToRoute('wixmp_sales_order_manage', ['storeHash' => $company->getStoreHash()]);
        }
    }   
    /**
     * @Route("/send_bulk_mail", name="send_bulk_mail")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("order", options={"mapping": {"order_id": "id"}})
     */
    public function send_bulk_mail(Request $request, CompanyApplication $companyApplication, PlatformHelper $platformHelper, SalesHelper $salesHelper)
    {
        $company = $companyApplication->getCompany();
        $requestData = $request->request->all();
        $orderIds = !empty($requestData['order_Ids']) ? explode(',',$requestData['order_Ids']) : [];

        $totalIds = count($orderIds);
        foreach($orderIds as $orderId) {
            $order = $salesHelper->get_order(['id'=>$orderId]);

            $company = $companyApplication->getCompany();
            list($order_response, $order_product, $platform_order_product, $shipping_response, $payout_data) = $salesHelper->get_order_view_control($order, $company);
            $shippingData = '';
            if(isset($shipping_response) && !empty($shipping_response)) {
                $shippingData = $shipping_response;
            }

            $bcOrderData = '';
            list($bcOrder,$params) = $platformHelper->get_platform_order_info($order->getStoreOrderId());
            $bcOrder = json_decode($bcOrder)->order;
            if(isset($bcOrder) && !empty($bcOrder)) {
                $bcOrderData = $bcOrder;
            }
            $email_helper = $this->wixMpBaseHelper->getHelper('email');
            $seller = $order->getSeller();
            $recipentEmail = $seller->getEmail();

            $productDatas = [];
            foreach($platform_order_product as $productData) {
                if(in_array($productData, array_keys($order_product))) {
                    array_push($productDatas, $productData);
                }
            }
            $appRunTime = new AppRuntime($this->wixMpBaseHelper->entityManager,$this->wixMpBaseHelper->container,$this->wixMpBaseHelper->logger); 
            if ($order->getStatus()) {
               $response = $email_helper->send_mail(
                    $recipentEmail,
                    [],
                    'seller/order_create',
                    'application',
                    array(
                        'company' => $companyApplication->getCompany(),
                        'seller' => $seller,
                        'payout' => $payout_data,
                        'order' => $order,
                        'product_list' => $platform_order_product,
                        'shipping_address' => $shippingData,
                        'bcOrder' => $bcOrderData ,
                        'application' => $companyApplication->getApplication(),
                    )
                );
            }

            $totalIds--;
            // return $this->send_mail($request, $companyApplication, $order, $platformHelper, $salesHelper); 
        }
        if($response){
            $notifications[] = array(
                'type' => 'success',
                'message' => $this->translate->trans('message.email_Sent_Success'),
            );
            $return = array(
                'totalCount' => count($orderIds),
                'notifications' => $notifications,
            );
        }else{
            $notification[] = array(
                'type' => 'danger',
                'message' => $this->translate->trans('message.email_not_sent'),
            );
            $return = array(
                'totalCount' => 0,
                'notifications' => $notifications,
            );
        }       
        // $return['redirect_url'] = $this->generateUrl('marketplace_sales_order_manage', ['storeHash' => $company->getStoreHash()]);
        return new JsonResponse($return);
    }
}

