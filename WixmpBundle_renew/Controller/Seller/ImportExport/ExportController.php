<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\ImportExport;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\Modules\Wix\WixmpBundle\Utils\CatalogHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Webkul\Modules\Wix\WixmpBundle\Utils\ImportExportHelper;
use Webkul\Modules\Wix\WixmpBundle\Form\Export\ProductExportType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
/**
 * @Route("/export", name="wixmp_seller_export_")
 * @Security("is_granted('ROLE_WIXMP_SELLER')")
 */
class ExportController extends BaseController
{
    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * @var ImportExportHelper
     */
    private $importExportHelper;

    private $limit;
    
    public function __construct(TranslatorInterface $translator, AdapterInterface $cache)
    {
        $this->translate = $translator;
        $this->cache = $cache;
    }
    /**
     * @Route("/product", name="product")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function get_seller_export_product(Request $request, CompanyApplication $companyApplication, $formId = 'ExportForm', CatalogHelper $catalogHelper)
    {
        $this->catalogHelper = $catalogHelper;
        $this->importExportHelper = $catalogHelper->getAppHelper('import_export');
        $this->catalogHelper->setCompanyApplication($companyApplication);
        $include_ids = [];
        $seller = $this->getUser()->getSeller() ?? null;
        $include_ids = [];
        // $include_ids = [160];
        $data = [
            'file_name' => 'products_'.rand().'.csv',
            'output_type' => 'D',
            'delimiter' => 'C',
            'categories_seperator' => '///',
        ];

        $product_fields = $this->catalogHelper->getProductFields();

        $product_fields['availability'] = [
            'field_id' => 'availability',
            'field_label' => 'availability',
        ];

        $product_fields['is_visible'] = [
            'field_id' => 'is_visible',
            'field_label' => 'is_visible',
        ];
        $primary_fields = [];
        $other_fields = [];

        foreach ($product_fields as $key => $value) {
            if (isset($value['is_primary'])) {
                $primary_fields[$key] = $key;
            } else {
                $other_fields[$key] = $key;
            }
        }
        $export_product_url = $this->generateUrl('mp_seller_export_product', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);

        $form = $this->createForm(ProductExportType::class, $data, ['action' => $export_product_url, 'export_fields' => ['primary_fields' => $primary_fields, 'other_fields' => $other_fields]]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $form_data = $form->getData();
            $params = [
                'primary_fields' => $primary_fields,
                'include_ids' => $include_ids,
                'form_data' => $form_data,
                'seller' => $seller,
            ];

            $return = $this->catalogHelper->handle_export_req($request, $params, $companyApplication);
            $output_params = [
                'storeHash' => $companyApplication->getCompany()->getStoreHash(),
                'filename' => $form_data['file_name'],
            ];
            if ($form_data['output_type'] == 'S') {
                $output_params['to_screen'] = 'Y';
            }
            $return['redirect_url'] = $this->generateUrl('mp_seller_export_get_file', $output_params);

            return new JsonResponse($return);
        } elseif ($form->isSubmitted()) {
            return new JsonResponse(
                array(
                    'status' => '400',
                    'notifications' => [
                        ['type' => 'danger', 'message' => 'invalid form request'],
                    ],
                    'items' => [],
                    'totalCOunt' => 0,
                )
            );
        }

        return $this->render('@marketplace_twig/view_templates/ImportExport/export_csv.html.twig', [
            'form' => $form->createView(),
            'formId' => $formId,
        ]);
    }

    /**
     * @Route("/sample_product_csv", name="sample_product_csv")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function sample_product_csv(Request $request, CompanyApplication $companyApplication, CatalogHelper $catalogHelper)
    {
        $return = ['status' => 200, 'notification' => ['type' => '', 'message' => '']];
        $pattern = [
            'enclosure' => $request->get('enclosure') ?? '"',
            'categories_seperator' => $request->get('categories_seperator') ?? '\\\\\\',
        ];
        $options = [
            'enclosure' => $request->get('enclosure') ?? '"',
            'delimiter' => $request->get('delimiter') ?? 'C',
            'filename' => $request->get('filename') ?? 'sample_file_'.time().'.csv',
        ];
        $catalogHelper->setCompanyApplication($companyApplication);
        $catalogHelper->create_sample_export_product_csv($pattern, $options);
        $return['notification'] = ['type' => 'success', 'message' => $this->translate->trans('exported_successfully')];
        $output_params = [
            'storeHash' => $companyApplication->getCompany()->getStoreHash(),
            'filename' => $options['filename'],
        ];
        $return['redirect_url'] = $this->generateUrl('wixmp_seller_export_get_file', $output_params);

        return new JsonResponse($return);
    }

    /**
     * @Route("/get_file", name="get_file")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function wk_get_file(Request $request, CompanyApplication $companyApplication, ImportExportHelper $importExportHelper)
    {
        $fileName = $request->get('filename');
        $file_csv_data = '';

        $file_csv_data_cache = $this->cache->getItem($companyApplication->getCompany()->getId().'_'.$fileName);
        if ($file_csv_data_cache->isHit()) {
            $file_csv_data = $file_csv_data_cache->get();
        }

        //$return = ['status' => 200, 'notification' => []];
        if (empty($file_csv_data)) {
            return new Response('NO Data to write CSV'); /* ['status' => 404, 'notification' => ['danger' => 'invalid form request']]; */
        }
        $to_screen = $request->get('to_screen');
        if (!empty($to_screen)) {
            header('Content-type: text/plain');
            // header("Content-Disposition: attachment; filename=".$fileName);
            echo $file_csv_data;
            exit;
        } else {
            header('Content-type: text/plain');
            header('Content-Disposition: attachment; filename='.$fileName);
            echo $file_csv_data;
            exit;
        }
        exit;
    }
}
