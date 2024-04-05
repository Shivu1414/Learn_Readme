<?php

/**
 * SMS Notification.
 *
 * PHP version 7.2
 *
 * @category Module
 *
 * @author    WebKul software private limited <support@webkul.com>
 * @copyright 2010 WebKul software private limited
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 * @version GIT:1.0
 *
 * @see Technical Support:  webkul.uvdesk.com
 */

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\IndexController;

use App\Helper\BaseHelper;
use App\Core\BaseController;
use Psr\Log\LoggerInterface;
use App\Entity\CompanyApplication;
use App\Form\Custom\CustomFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;
use Symfony\Component\HttpFoundation\Response;


/**
 * DefaultController class for SMS notification.
 *
 * @category Module
 *
 * @author   WebKul software private limited <support@webkul.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 * @see     Technical Support:  webkul.uvdesk.com
 *
 * @Route("/", name="wixmp_")
 *
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class DefaultController extends BaseController
{
    /**
     * Base Helper.
     *
     * @var object
     */
    private $_helper;

    /**
     * Allowed Event Names.
     *
     * @var array
     */
    private $_settingsType;

    private $_logger;

    const SHOW_LIMIT_RANGE = [10, 20, 30, 40];

    /**
     * Constructor.
     *
     * @param BaseHelper      $helper basehelper
     * @param LoggerInterface $logger Logger
     */
    public function __construct(BaseHelper $helper, LoggerInterface $logger)
    {
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_settingsType = array(
            'order_create', 'update_order', 'shipment_create',
            'create_customer', 'abandoned_conversion',
            'low_stock_for_admin',
        );
    }

    /**
     * Function to save settings.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Application of Company
     *
     * @Route("/",        name="index")
     * @Route("/dashboard", name="dashboard")
     *
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     *
     * @return object
     */
    public function indexAction(
        Request $request,
        CompanyApplication $companyApplication,
        WixMpBaseHelper $mpBaseHelper
    ) {
        
        // return $this->redirectToRoute("wixmp_catalog_product_manage",[
        //     'storeHash' => $request->get('storeHash')
        // ]);
       
        $dashboardData = $request->request->get('dashboard');
        $filters = isset($dashboardData['filter']) ? $dashboardData['filter'] : [];
        if (empty($filters)) {
            //filters not set assign default
            $filters = array(
                'seller_status' => ['PAID','ORDER_FULFILLED','PARTIALLY_PAID'],
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

        $salesHelper = $mpBaseHelper->getAppHelper('sales');
        // get report data
        $reportData = $salesHelper->generateReportData($request, $filters);

        $catalogHelper = $mpBaseHelper->getAppHelper('catalog');
        $userHelper = $mpBaseHelper->getAppHelper('user');
        
        $user_params['company'] = $product_params['company'] = $order_params['company'] = $company = $companyApplication->getCompany();

        // $user_params['seller'] = $product_params['seller'] = $order_params['seller'] = $seller = $this->getUser()->getSeller();
        $order_params['parent_only'] = 'N';
        $user_params['items_per_page'] = $product_params['items_per_page'] = $order_params['items_per_page'] = '5';

        list($orders) = $salesHelper->get_orders($order_params);
        list($products) = $catalogHelper->get_products($product_params);
        list($users) = $userHelper->get_users($user_params);
        
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'dashboard/dashboard',
            'title' => 'admin_dashboard',
            'downgrade' => true,
            'order_list' => $orders,
            'product_list' => $products,
            'user_list' => $users,
            'filter' => $filters,
            'reportData' => $reportData,
            'company' => $companyApplication->getCompany(),
        ]);
    }

    /**
     * @Route("/theme/update", name="update_theme")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function themeUpdate(Request $request, CompanyApplication $companyApplication, BaseHelper $baseHelper)
    {
        $themeName = $request->query->get('theme');
        if (empty($themeName)) {
            throw new \Exception('Theme not found');
        }
        // save to settings
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        $commonHelper = $baseHelper->getHelper('common');
        $settingInfo = $commonHelper->update_section_setting($themeName, 'theme', 'general', $company, $application, 1);

        if (!empty($settingInfo)) {
            return new Response('true');
        }

        return new Response('false');
    }
}
