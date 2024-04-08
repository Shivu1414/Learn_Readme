<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\ImportExport;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Webkul\Modules\Wix\WixmpBundle\Utils\CatalogHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Webkul\Modules\Wix\WixmpBundle\Utils\ImportExportHelper;
use Webkul\Modules\Wix\WixmpBundle\Form\Import\ProductImportType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * @Route("/import", name="wixmp_import_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class ImportController extends BaseController
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

    /**
     * @Route("/product", name="product")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function get_admin_import_product(Request $request, CompanyApplication $companyApplication, $formId = 'ExportForm', CatalogHelper $catalogHelper)
    {
        $this->catalogHelper = $catalogHelper;
        $this->importExportHelper = $catalogHelper->getAppHelper('import_export');
        $this->importExportHelper->setCompanyApplication($companyApplication);

        $data = [
            'delimiter' => 'C',
            'categories_seperator' => '///',
        ];

        $product_fields = $this->catalogHelper->getProductFields();
        $primary_fields = [];
        $other_fields = []; 
        foreach ($product_fields as $key => $value) { 
            if (isset($value['is_primary'])) {
                $primary_fields[$key] = $key;
            } else {
                $other_fields[$key] = $key;
            }
        } 
        $import_product_url = $this->generateUrl('wixmp_import_product', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        
        $form = $this->createForm(ProductImportType::class, $data, ['action' => $import_product_url]);
        // $import_fields = [ 'primary_fields' => $primary_fields, 'other_fields' => $other_fields ];
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $return = ['status' => 200, 'notfication' => []];
            $form_data = $form->getData();
            $params = [
                'primary_fields' => $primary_fields,
                'form_data' => $form_data,
                'seller' => null,
                'file' => $form['file']->getData(),
            ];
            $response = $this->catalogHelper->handle_import_req($request, $params, $companyApplication);

            return new JsonResponse($response);
        } elseif ($form->isSubmitted()) {
            
            $errors = [];
            foreach ($form as $fieldName => $formField) {
               
                if (!empty($formField->getErrors()->__toString())) {
                    $errors = ['type' => 'danger', 'message' => $formField->getErrors()->__toString() ];
                }
            }
            
            return new JsonResponse(
                array(
                    'status' => '400',
                    'notifications' => [
                        $errors,
                    ],
                    'items' => [],
                    'totalCount' => 0,
                )
            );
        }

        return $this->render('@wixmp_twig/view_templates/ImportExport/import_csv.html.twig', [
            'form' => $form->createView(),
            'formId' => $formId,
            'primary_fields' => $primary_fields,
            'other_fields' => $other_fields,
        ]);
    }
}
