<?php

namespace Webkul\Modules\Wix\WixChatgptBundle\Controller;

use App\Helper\BaseHelper;
use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Utils\Platform\Wix\WixClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webkul\Modules\Wix\WixChatgptBundle\Utils\PlatformHelper;
use Webkul\Modules\Wix\WixChatgptBundle\Utils\ChatGPTHelper;
use Webkul\Modules\Wix\WixChatgptBundle\Utils\WixChatGPTHelper;

/**
 *
 *
 * @author   WebKul software private limited <support@webkul.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 * @see     Technical Support:  webkul.uvdesk.com
 *
 * @Route("/", name="wixchatgptcontent_")
 * @Security("is_granted('ROLE_WIXCHATGPTCONTENT_ADMIN')")
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
     * Constructor.
     *
     * @param BaseHelper      $helper basehelper
     */
    public function __construct(BaseHelper $helper, ContainerInterface $container, EntityManagerInterface $entityManager,EventDispatcherInterface $dispatcher, PlatformHelper $platformHelper, ChatGPTHelper $chatGPTHelper, WixChatGPTHelper $wixChatGPTHelper) {
        $this->container = $container;
        $this->_helper = $helper;
        $this->translate = $this->container->get('translator');
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
        $this->platformHelper = $platformHelper;
        $this->chatGPTHelper = $chatGPTHelper;
        $this->wixChatGPTHelper = $wixChatGPTHelper;
        // dd($this->wixChatGPTHelper);

    }

    /**
     * Function for Auctions Setup
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
    public function indexAction( Request $request, CompanyApplication $companyApplication ) {
        $requestParams = $request->request->all();
        $company = $companyApplication->getCompany();

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
            if (isset($requestParams['description']) && isset($requestParams['productId'])) {

                $platform_product_data = [
                    'product' => [
                        'description' => str_replace("\n","<br>", $requestParams['description'])
                    ]
                ];
                list($response, $error) = $this->platformHelper->update_product($requestParams['productId'], $platform_product_data);

                return new Response(json_encode(['status' => "SUCCESS", "message" => "Description Updated"]));

            } else {
                return new Response(json_encode(['status' => "FAILD", "message" => "Please Send valid data"]));
            }
            
        } else {
            
            return $this->render('@wixchatgptcontent_twig/view_templates/index.html.twig', [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'products/product_desc_update',
                'title' => 'Products',
            ]);
        }
    }

    /**
     * @Route("/product-search", name="product_search")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     */
    public function productSearch(Request $request, CompanyApplication $companyApplication) {

        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        $requestParams = $request->query->all();
        $params = [
            "query" => [
                "filter" => json_encode([
                    "name" => [ '$contains' => isset($requestParams['search']) ?$requestParams['search'] : "" ]
                ])
            ]
        ];
        $productsData = [];
        $searchProducts = $this->platformHelper->get_platform_products($params);
        $product  = isset($searchProducts[0]) ? json_decode($searchProducts[0]) : "";
        if (isset($product->products)) foreach ($product->products as $productValue) {
            $productsData[] = [
                "productId" => $productValue->id,
                "productName" => $productValue->name
            ];
        }
        
        return new Response(json_encode(['status' => "SUCCESS", "data" => $productsData]));
    
    }
    
    /**
     * @Route("generate-product-description", name="description_generate")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function descriptionGenerate(Request $request, CompanyApplication $companyApplication) {
        $requestParams = $request->request->all();
        if ( isset($requestParams['product']) && isset($requestParams['totalWord']) && $requestParams['descType'] != "custom") {

            $writeText = "write a description for {$requestParams['product']} {$requestParams['additionalInfo']} in  {$requestParams['totalWord']}  as {$requestParams['descType']} ";

        } elseif (isset($requestParams['descType']) && $requestParams['descType'] == "custom" && isset($requestParams['customDesc'])) {
            $writeText  = "write a description for {$requestParams['product']} {$requestParams['additionalInfo']} in  {$requestParams['totalWord']}  as {$requestParams['customDesc']} ";
        } else {
            return new Response(json_encode(['status' => "FAILD", 'content' => "Please send valid request" ]));
        }

        $params = [
            'model' => "gpt-3.5-turbo",
            'messages' => [
                [
                    'role' => "user",
                    'content' => $writeText,
                ]
            ],
        ];
        
        $content = json_decode($this->chatGPTHelper->contentGenerate($params));
        if (isset($content->choices)) {
            foreach ($content->choices as $choice) {
                if (isset($choice->message->role) && isset($choice->message->content)) {
                    $role = $choice->message->role;
                    $generatesContent = $choice->message->content;
                }
            }
            return new Response(json_encode(['status' => "SUCCESS", 'content' => $generatesContent ]));
        } else {
            if ($content->error) {
                return new Response(json_encode(['status' => "FAILD", 'content' => "Please Check Your Details", 'message' => isset($content->error->message) ? $content->error->message : null, 'code' => isset($content->error->code) ? $content->error->code : null ]));
            } else {
                return new Response(json_encode(['status' => "FAILD", 'content' => "Please Check Your Details" ]));
            }
        }
    }
    
    /**
     * @Route("product/seo", name="product_seo")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function productSeo(Request $request, CompanyApplication $companyApplication) {
        $requestParams = $request->request->all();
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {

            if (!isset($requestParams['productId'])) {
                return new Response(json_encode(['status' => "FAILD", "message" => "Please Send valid Product"]));
            }

            $this->wixChatGPTHelper->productSeoUpdate($requestParams);
            return new Response(json_encode(['status' => "SUCCESS", "message" => "Seo Tags Updated"]));

        } else {

            return $this->render('@wixchatgptcontent_twig/view_templates/index.html.twig', [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'products/product_seo',
                'title' => 'Product SEO',
            ]);
        }
    }

    /**
     * @Route("generate-product-seo", name="seo_generate")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function seoTagGenerate(Request $request, CompanyApplication $companyApplication) {
        $requestParams = $request->request->all();

        if (isset($requestParams['productId']) && isset($requestParams['additionalInfo']) && $requestParams['additionalInfo']  != "productname" ) {
            $response = $this->platformHelper->get_platform_product($requestParams['productId']);
            $productDetails = json_decode($response[0]);
            
            if ( isset($productDetails->product) && isset($productDetails->product->description) ) {
                $generateByDesc = $productDetails->product->description;
            }
        } 

        if ( isset($requestParams['product-name']) && $requestParams['descriptionType'] != "custom") {
            
            $generateBy = isset($generateByDesc) ?  $generateByDesc : $requestParams['product-name'];
            $product = "wite write seo tag for $generateBy as {$requestParams['descriptionType'] }";
            $urltag = isset($requestParams['urlCheck']) ? "url tag in " . $requestParams['wordOfUrl'] . " word " : "" ;
            $title = isset($requestParams['titleCheck']) ? " title tag in " . $requestParams['wordOfTitle'] . " word " : "";
            $meta =  isset($requestParams['metaCheck']) ? " meta description tag in " . $requestParams['wordOfMeta'] . " word " : "";
            $writeText = $product . $urltag . $title . $meta;

        } elseif (isset($requestParams['descType']) && $requestParams['descriptionType'] == "custom" && isset($requestParams['customDesc'])) {
            
            $generateBy = isset($generateByDesc) ?  $generateByDesc : $requestParams['product-name'];
            $product = "wite write seo tag for $generateBy  as {$requestParams['customDesc'] }";
            $urltag = isset($requestParams['urlCheck']) ? "url tag in " . $requestParams['wordOfUrl'] . " word " : "" ;
            $title = isset($requestParams['titleCheck']) ? " title tag in " . $requestParams['wordOfTitle'] . " word " : "";
            $meta =  isset($requestParams['metaCheck']) ? " meta description tag in " . $requestParams['wordOfMeta'] . " word " : "";
            $writeText = $product . $urltag . $title . $meta;

        } else {
            
            return new Response(json_encode(['status' => "FAILD", 'content' => "Please send valid request" ]));
        }

        $params = [
            'model' => "gpt-3.5-turbo",
            'messages' => [
                [
                    'role' => "user",
                    'content' => $writeText,
                ]
            ],
        ];
        $content = json_decode($this->chatGPTHelper->contentGenerate($params));
        
        if (isset($content->choices)) {
            foreach ($content->choices as $choice) {
                if (isset($choice->message->role) && isset($choice->message->content)) {
                    $role = $choice->message->role;
                    $generatesContent = $choice->message->content;
                    
                    if (preg_match('/Conversational URL tag: (.*?)$/i', $generatesContent, $matches)) {
                        $titleTag = $matches[1];
                    }

                    if (preg_match('/Title tag:\s(.*?)(?:\n|$)/i', $generatesContent, $matches)) {
                        $titleTag = $matches[1];
                    }

                    if (preg_match('/Meta description tag \(\d+ characters\):\s(.*?)(?:\n|$)/i', $generatesContent, $matches)) {
                        $metaDescription = $matches[1];
                    }

                    if (preg_match('/Meta Description Tag: (.*?)(?:\r?\n|$)/im', $generatesContent, $matches)) {
                        $metaDescription = $matches[1];
                    }

                    $urlTag = null;
                    $titleTag = isset($titleTag) ? $titleTag : null;
                    $metaDescTag = isset($metaDescription) ? $metaDescription : null;
                }
            }

            $updateProdcut = [
                'productId' => $requestParams['productId'],
                'seoUrl' => $urlTag,
                'seoTitle' => $titleTag,
                'seoMetaDesc' => $metaDescTag,
            ];
            $this->wixChatGPTHelper->productSeoUpdate($updateProdcut);
            return new Response(json_encode(['status' => "SUCCESS", 'message' => "SEO Content Successfully Updated" ]));
        
        } else {
            if ($content->error) {
                return new Response(json_encode(['status' => "FAILD", 'content' => "Please Check Your Details", 'message' => isset($content->error->code) ? $content->error->code : null ]));
            } else {
                return new Response(json_encode(['status' => "FAILD", 'content' => "Please Check Your Details" ]));
            }
        }
    }

    /**
     * @Route("/redirect-blog/", name="userguide_blog")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function redirectToBlogPage(Request $request, CompanyApplication $companyApplication){
        return $this->redirect("https://webkul.com/blog/user-guide-for-chatgpt-seo-and-product-content-app-for-wix/");
    }

 
}
