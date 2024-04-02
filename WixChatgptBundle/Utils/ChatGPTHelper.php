<?php

namespace Webkul\Modules\Wix\WixChatgptBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\Modules\Wix\WixChatgptBundle\Utils\WixChatGPTHelper;

class ChatGPTHelper {

    private $apiUrl;
    private $curl;

    public function __construct(ContainerInterface $container, WixChatGPTHelper $wixChatGPTHelper) {

        $this->container = $container;
        $this->wixChatGPTHelper = $wixChatGPTHelper;
        $companyApplication = $this->container->get('app.runtime')->get_company_application();
        $this->apiUrl = "https://api.openai.com/v1";

        if (!empty($companyApplication)) {
            $settingsValue = $this->wixChatGPTHelper->getGeneralConfigData($companyApplication);
            if ( isset($settingsValue['wixchatgptcontent_api_secret']) && $settingsValue['wixchatgptcontent_api_secret'] ) {
                $this->secretKey = $settingsValue['wixchatgptcontent_api_secret'];
            } else {
                $this->secretKey = null;
            }
        }

    }

    public function setInit($url = "") {

        $this->curl = curl_init($url);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }

    public function setHeader($header = []) {

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
    
    }

    public function setPostData($data = [], $isJson = FALSE) {
        curl_setopt($this->curl, CURLOPT_POST, true);
        if (!$isJson) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES));
        } else {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        }
        
    }

    public function setCurlSSL($flag = false) {

        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, $flag);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $flag);
    }

    public function execute() {

        $response = curl_exec($this->curl);
        curl_close($this->curl);
        
        return $response;
    }

    public function contentGenerate($params) {

        $url = $this->apiUrl.'/chat/completions';
        $headers = array(
            "Authorization: Bearer $this->secretKey",
            "Content-Type: application/json",
        );
        $this->setInit($url);
        $this->setHeader($headers);
        $this->setPostData($params);
        $this->setCurlSSL();
        
        $response = $this->execute();
        return $response;

    }

}