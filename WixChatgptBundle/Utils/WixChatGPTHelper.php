<?php

namespace Webkul\Modules\Wix\WixChatgptBundle\Utils;

use App\Helper\BaseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\Modules\Wix\WixChatgptBundle\Utils\PlatformHelper;

class WixChatGPTHelper extends BaseHelper {

    private $helper;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, BaseHelper $helper, PlatformHelper $platformHelper) {

        $this->entityManager = $entityManager;
        $this->container = $container;
        $this->translate = $this->container->get('translator');
        $this->dispatcher = $this->container->get('event_dispatcher');
        $companyApplication = $this->container->get('app.runtime')->get_company_application();
        $this->helper = $helper;
        $this->platformHelper = $platformHelper;

    }

    public function getGeneralConfigData($companyApplication) {
        $application = $companyApplication->getApplication();
        $company = $companyApplication->getCompany();
        $commonHelper = $this->getHelper('common');
        $section = 'wixchatgptcontent_general';

        $data = $commonHelper->get_section_setting(
            [
                'sectionName' => $section, 
                'company' => $company,
                'application' => $application
            ],
            true
        );

        $configData = [];
        foreach ($data as $fieldName => $fieldValue) {
            $val = $fieldValue->getValue();
            if (empty($val)) {
                $val = false;
            }
            if ($val == '1' || $val == '0') {
                $val = (bool) $val;
            }
            $configData[$fieldName] = $val;
        }
        return $configData;
    }

    public function productSeoUpdate($params) {
        if (!isset($params['productId'])) {
            return false;
        }

        // $urlTag  =  isset($params['seoUrl']) ? [
        //     "type" => "SetProductUrlPart",
        //     "props" => [
        //         "name" => "link",
        //         "url" => $params['seoUrl']
        //     ]
        // ] : null;

        $titleTag  =  isset($params['seoTitle']) ? [
            "type" => "title",
            "children" => $params['seoTitle']
        ] : null;

        $metaTag  =  isset($params['seoMetaDesc']) ? [
            "type" => "meta",
            "props" => [
                "name" => "description",
                "content" => $params['seoMetaDesc']
            ]
        ] : null;

        $platform_product_data = [
            'product' => [
                'seoData' => [
                    'tags' => [
                        $titleTag, $metaTag, //$urlTag
                    ],
                ]
            ]
        ];
        list($response, $error) = $this->platformHelper->update_product($params['productId'], $platform_product_data);
        return true;
    }
    

}