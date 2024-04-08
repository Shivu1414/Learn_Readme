<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\ApiController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\Modules\Wix\WixmpBundle\Entity\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Webkul\Modules\Wix\WixmpBundle\Form\Api\ApiType;
/**
 * @Route("/api", name="wixmp_api_")
 * @Security("is_granted('ROLE_SUPER_ADMIN')")
 */
class ApiController extends BaseController
{
    public function __construct(TranslatorInterface $translator, AdapterInterface $cache, EntityManagerInterface $entityManager)
    {
        $this->translate = $translator;
        $this->cache = $cache;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/playground/", name="playground")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function apiPlayground(CompanyApplication $companyApplication, Request $request, WixMpBaseHelper $baseHelper): Response
    {
        $platformHelper = $baseHelper->getAppHelper('platform');

        $form = $this->createForm(ApiType::class);
        $form->handleRequest($request);

        $apiResponse = "";

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            
            list($apiResponse, $error) = $platformHelper->callApi(
                isset($formData['apiUrl']) ? $formData['apiUrl'] : "",
                isset($formData['method']) ? $formData['method'] : "POST",
                isset($formData['body']) ? $formData['body'] : json_encode([]),
            );
        }
        //dd(json_encode(json_decode($apiResponse),JSON_PRETTY_PRINT));
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'api/api_playground',
            'title' => 'api_playground',
            'form' => $form->createView(),
            "apiResponse" => json_encode(json_decode($apiResponse),JSON_PRETTY_PRINT),
        ]);
    }
}
?>