<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\IndexController;

use App\Core\BaseController;
use App\Helper\BaseHelper;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;

/**
 * @Route("/", name="wixmp_seller_")
 * @Security("is_granted('ROLE_WIXMP_SELLER')")
 */
class DefaultController extends BaseController
{
    public function __construct(EventDispatcherInterface $dispatcher, ContainerInterface $container, BaseHelper $baseHelper)
    {
        $this->translate = $container->get('translator');
        $this->dispatcher = $dispatcher;
        $this->session = $container->get('session');
        $this->container = $container;
        $this->baseHelper = $baseHelper;
    }

    /**
     * @Route("/", name="index")
     * @Route("/dashboard", name="dashboard")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function indexAction(Request $request, CompanyApplication $companyApplication, WixMpBaseHelper $wixMpBaseHelper)
    {
        // return $this->redirectToRoute("wixmp_seller_catalog_product_manage",[
        //     'storeHash' => $request->get('storeHash')
        // ]);

        $dashboardData = $request->request->get('dashboard');
        $filters = isset($dashboardData['filter']) ? $dashboardData['filter'] : [];
        if (empty($filters)) {
            //filters not set assign default
            $filters = array(
                'seller_status' => ['PAID','ORDER_PAID','ORDER_FULFILLED','PARTIALLY_FULFILLED'],
                'from_date' => date('Y-m-d', strtotime('first day of january this year')), //date('Y-m-01'),
                'to_date' => date('Y-m-d', time()), //date('Y-m-t')
            );
        }
        if (isset($filters['from_date']) && !empty($filters['from_date'])) {
            $filters['start_date'] = strtotime($filters['from_date']);
        }
        if (isset($filters['to_date']) && !empty($filters['to_date'])) {
            $tempTS = strtotime($filters['to_date']);
            $filters['end_date'] = strtotime('tomorrow', $tempTS) - 1; // to get timestam of last min of  last day
        }
        // set company
        $filters['company'] = $companyApplication->getCompany();
        $filters['seller'] = $this->getUser()->getSeller();

        $salesHelper = $wixMpBaseHelper->getAppHelper('sales');
        // get report data
        $reportData = $salesHelper->generateReportData($request, $filters);

        $catalogHelper = $wixMpBaseHelper->getAppHelper('catalog');
        $userHelper = $wixMpBaseHelper->getAppHelper('user');
        $sellerHelper = $wixMpBaseHelper->getAppHelper('seller');
        $mediaHelper = $this->baseHelper->getHelper('media');

        $user_params['company'] = $product_params['company'] = $order_params['company'] = $company = $companyApplication->getCompany();
        $user_params['seller'] = $product_params['seller'] = $order_params['seller'] = $seller = $this->getUser()->getSeller();
        $order_params['parent_only'] = 'N';
        $user_params['items_per_page'] = $product_params['items_per_page'] = $order_params['items_per_page'] = '5';

        if ($this->getUser()->getIsRoot() != 'Y') {
            $user_params['id'] = $this->getUser()->getId();
        }
        
        list($orders) = $salesHelper->get_orders($order_params);
        list($products) = $catalogHelper->get_products($product_params);
        list($users) = $userHelper->get_users($user_params);
        
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'dashboard/dashboard',
            'title' => 'seller_dashboard',
            'order_list' => $orders,
            'product_list' => $products,
            'user_list' => $users,
            'filter' => $filters,
            'reportData' => $reportData,
            'company' => $companyApplication->getCompany(),
            'seller' => $seller
        ]);
    }

    /**
     * @Route("/contact_to_admin", name="contact_to_admin")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function contact_to_admin(Request $request, CompanyApplication $companyApplication, WixMpBaseHelper $marketplaceBaseHelper)
    {
        // email will go here
        $data = $request->request->All();
        $company = $companyApplication->getCompany();

        // $SellerEvent = new SellerEvent($companyApplication, $this->getUser()->getSeller(), $this->getUser());
        // $SellerEvent->setBodyText($data);
        // $this->dispatcher->dispatch($SellerEvent, SellerEvent::CONTACT_TO_ADMIN);

        $this->addFlash('success', $this->translate->trans('message.common.message_sent_successfully'));

        return $this->redirectToRoute('mp_seller_dashboard', ['storeHash' => $company->getStoreHash()]);
    }
}