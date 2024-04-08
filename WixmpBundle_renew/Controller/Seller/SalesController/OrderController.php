<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\SalesController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Webkul\Modules\Wix\WixmpBundle\Utils\SalesHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders;
use Webkul\Modules\Wix\WixmpBundle\Utils\PlatformHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\Modules\Wix\WixmpBundle\Twig\AppRuntime;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;
use Webkul\Modules\Wix\WixmpBundle\Form\Sales\OrderStatusType;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sales", name="wixmp_seller_sales_order_")
 * @Security("is_granted('ROLE_WIXMP_SELLER')")
 */
class OrderController extends BaseController
{
    public function __construct(TranslatorInterface $translator, WixMpBaseHelper $WixMpBaseHelper, KernelInterface $kernel, Pdf $pdf)
    {
        $this->translate = $translator;
        $this->WixMpBaseHelper = $WixMpBaseHelper;
        $this->kernel = $kernel;
        $this->pdf = $pdf;
    }

    /**
     * @Route("/order/", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function order_manage(Request $request, CompanyApplication $companyApplication, SalesHelper $salesHelper)
    {
        $form = $this->createForm(OrderStatusType::class);
        $params = $request->query->all();
        $params['company'] = $company = $companyApplication->getCompany();
        $params['seller'] = $seller = $this->getUser()->getSeller();
        $params['parent_only'] = 'N';
        
        if (isset($params['seller_status']) && $params['seller_status'] != "" && isset($params['include_incomplete_orders'])) {
            $seller_status = $params['seller_status'];
            $params['seller_status'] = [];
            
            $params['seller_status'][] = $seller_status;
            $params['seller_status'][] = 0;
        }
        $order_statuses = $salesHelper->get_order_status_list();
        $fullfillment_order_statuses = $salesHelper->get_order_fullfillment_status_list();

        list($orders, $params) = $salesHelper->get_orders($params);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestData = $request->request->all();

            if (
                isset($requestData['order_status']['seller_order_fullfillment_batch_action']) && 
                $requestData['order_status']['seller_order_fullfillment_batch_action'] == 1
            ) {
                $entityManager = $this->getDoctrine()->getManager();
                $orderIds = isset($requestData['order_ids']) ? $requestData['order_ids'] : [];
                foreach($orderIds as $orderId) {
                    $order = $salesHelper->get_order(["id" => $orderId]);
                    $order->setSellerFullfillmentStatus(1);
                    $entityManager->persist($order);
                    $entityManager->flush();
                }
                $this->addFlash('success', 'Batch action completed !' );
            } 
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
            'wix_fullfillment_order_statuses' => $fullfillment_order_statuses,
            'form' => $form->createView(),
        ]);
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
            return $this->redirectToRoute('wixmp_seller_sales_order_manage', ['storeHash' => $company->getStoreHash()]);
        }
    }

    /**
     * @Route("/{order_id}/fullfillment", name="fullfillment")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("order", options={"mapping": {"order_id": "id"}})
     */
    public function updateFullFillmentStatus(
        Request $request, CompanyApplication $companyApplication, 
        SellerOrders $order,SalesHelper $salesHelper
    ) {
        $fullfillmentStatus = $request->request->get("fullfillmentStatus");
        $entityManager = $this->getDoctrine()->getManager();
        $order->setSellerFullfillmentStatus($fullfillmentStatus);
        $entityManager->persist($order);
        $entityManager->flush();

        //return $this->redirectToRoute('wixmp_seller_sales_order_manage', ['storeHash' => $company->getStoreHash()]);
        return new Response("ok");
    }
}