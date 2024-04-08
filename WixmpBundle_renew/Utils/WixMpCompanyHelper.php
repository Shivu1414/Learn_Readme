<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

use App\Entity\CompanyApplication;
use Doctrine\DBAL\DBALException;
use Webkul\Modules\Bigcommerce\MarketplaceBundle\Entity\SellerPlan;
use Webkul\Modules\Bigcommerce\MarketplaceBundle\Entity\Seller;
use App\Entity\Media;

class WixMpCompanyHelper extends WixMpBaseHelper
{
    public function get_company_plan_validated_data(CompanyApplication $companyApplication)
    {
        $current_subscription = $companyApplication->getSubscription();
        if ($current_subscription) {
            $current_plan = $current_subscription->getPlanApplication()->getPlan();
            $currenct_plan_app_features = $current_subscription->getPlanApplication()->getFeatures();
        } else {
            $current_plan = null;
            $currenct_plan_app_features = ['max_seller' => null, 'max_product' => null];
        }
        
        $validation_data = false;
        if ($current_plan && $current_plan->getStatus() == 'A') {
            $validation_data['transaction_fees'] = $current_plan->getPrice();
            $validation_data['plan_state'] = $current_subscription->getStatus();  //Active or Expired
            $validation_data['max_seller'] = isset($currenct_plan_app_features['max_sellers']) ? $currenct_plan_app_features['max_sellers'] : "";
            $validation_data['max_product'] = isset($currenct_plan_app_features['max_products']) ? $currenct_plan_app_features['max_products'] : "";
            $validation_data['current_seller'] = 0;
            $validation_data['current_product'] = 0;
            $validation_data['plan_upto'] = $current_subscription->getNextBillingDate();
            $validation_data['allow'] = array(
                'product' => false,
                'seller' => false,
            );

            $sellerHelper = $this->getAppHelper('seller');
            $seller_data = $sellerHelper->get_all_sellers(['company' => $companyApplication->getCompany()->getId(),'status' => ['A', 'D'], 'isArchived' => 0,]);
            $current_seller = count($seller_data);

            $validation_data['current_seller'] = $current_seller;

            if ($validation_data['max_seller'] == 0 || $current_seller < $validation_data['max_seller']) {
                $validation_data['allow']['seller'] = true;
            }

            $catalogHelper = $this->getAppHelper('catalog');
            // $CatalogHelper = new CatalogHelper($this->logger, $this->entityManager, $this->container, $this->platformHelper, $this->mailer);
            $product_count = $catalogHelper->get_product_count($companyApplication->getCompany()->getId(), null);

            if ($validation_data['max_product'] == 0 || $product_count[1] < $validation_data['max_product']) {
                $validation_data['allow']['product'] = true;
            }

            $validation_data['current_product'] = $product_count[1];
        }

        return $validation_data;
    }

    public function getUnarchivedSellerValidCount(CompanyApplication $companyApplication)
    {
        $current_subscription = $companyApplication->getSubscription();
        if ($current_subscription) {
            $current_plan = $current_subscription->getPlanApplication()->getPlan();
            $current_plan_app_features = $current_subscription->getPlanApplication()->getFeatures();
        } else {
            $current_plan = null;
            $current_plan_app_features = ['max_seller' => null, 'max_product' => null];
        }
        
        $validation_data = false;
        if ($current_plan && $current_plan->getStatus() == 'A') {
            $validation_data['max_seller'] = isset($current_plan_app_features['max_sellers']) ? $current_plan_app_features['max_sellers'] : "";
            $validation_data['current_seller'] = 0;
            $validation_data['allow'] = array(
                'seller' => false,
            );

            $sellerHelper = $this->getAppHelper('seller');
            $seller_data = $sellerHelper->get_all_sellers([
                'company' => $companyApplication->getCompany()->getId(),
                'status' => ['A', 'D'],
                'isArchived' => 0,
            ]);
            $current_seller = count($seller_data);

            $validation_data['current_seller'] = $current_seller;

            if ($validation_data['max_seller'] == 0 || $current_seller < $validation_data['max_seller']) {
                $validation_data['allow']['seller'] = true;
            }
        }
        
        return $validation_data;
    }
}