<?php

namespace Webkul\Modules\Wix\WixmpBundle\Events;

use App\Entity\Company;
use Symfony\Contracts\EventDispatcher\Event;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerUser;
use Symfony\Component\HttpFoundation\Request;

class SellerEvent extends Event
{
    const WIX_SELLER_COMPANY_PRE_ADD = 'wix.seller.company.pre.add';
    const WIX_SELLER_ACCOUNT_REGISTER = 'wix.seller.account_register';
    const WIX_SELLER_COMPANY_PRE_STATUS_CHANGE = 'wix.seller.company.pre.status_change';
    const WIX_SELLER_ADMIN_CREATE = 'wix.seller.admin.create';
    const WIX_SELLER_STATUS_CHANGE = 'wix.seller.status.change';
    const WIX_SELLER_COMPANY_UPDATE = 'wix.seller.company.update';
    const WIX_SELLER_ORDER_CREATE = 'wix.seller.order.create';
    const WIX_SELLER_ORDER_STATUS_CHANGE = 'wix.seller.order.status.change';
    const WIX_SELLER_ACCOUNT_PAYOUT_STATUS_CHANGE = 'wix.seller.account.payout.status_change';
    const WIX_SELLER_ACCOUNT_PAYOUT_CREATE = 'wix.seller.account.payout.create';
    const WIX_SELLER_PLAN_BUY = 'wix.seller.plan_buy';
    const WIX_SELLER_ACCOUNT_WITHDRAWAL_REQUEST = 'wix.seller.account.withdrawal.request';
    const WIX_SELLER_FORGOT_PASSWORD = 'wix.seller.forgot_password';
    const WIX_SELLER_UNARCHIVE_STATUS = "wix.seller.unarchive.status";

    private $companyApplication;
    private $request;
    /**
     * @var Seller
     */
    protected $seller;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var SellerUser
     */
    protected $user;

    protected $payout;

    protected $order;
    protected $wixOrder;
    protected $shippingAddress;
    protected $orderProducts;

    /**
     * @var string
     */
    protected $bodyText;
    private $actionAllowed;

    public function __construct($companyApplication, Seller $seller = null, SellerUser $user = null, Request $request = null)
    {
        $this->companyApplication = $companyApplication;
        $this->seller = $seller;
        if ($seller != null) {
            $this->company = $seller->getCompany();
        }
        $this->user = $user;
        $this->request = $request;
    }

    public function getSeller()
    {
        return $this->seller;
    }

    public function getCompanySeller()
    {
        return $this->seller;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get the value of bodyText.
     *
     * @return string
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * Set the value of bodyText.
     *
     * @param string $bodyText
     *
     * @return self
     */
    public function setBodyText($bodyText)
    {
        $this->bodyText = $bodyText;

        return $this;
    }

    /**
     * Get the value of companyApplication.
     */
    public function getCompanyApplication()
    {
        return $this->companyApplication;
    }

    /**
     * Set the value of companyApplication.
     *
     * @return self
     */
    public function setCompanyApplication($companyApplication)
    {
        $this->companyApplication = $companyApplication;

        return $this;
    }

    /**
     * Get the value of actionAllowed.
     */
    public function getActionAllowed()
    {
        return $this->actionAllowed;
    }

    /**
     * Set the value of actionAllowed.
     *
     * @return self
     */
    public function setActionAllowed($actionAllowed)
    {
        $this->actionAllowed = $actionAllowed;

        return $this;
    }

    /**
     * Get the value of payout.
     */
    public function getPayout()
    {
        return $this->payout;
    }

    /**
     * Set the value of payout.
     *
     * @return self
     */
    public function setPayout($payout)
    {
        $this->payout = $payout;

        return $this;
    }

    /**
     * Get the value of order.
     */
    public function getOrder()
    {
        return $this->order;
    }
    /**
     * Set the value of order.
     *
     * @return self
     */
    public function setOrder($order)
    {        
        $this->order = $order;
        return $this;
    }
    /**
     * Set the value of bc  order.
     *
     * @return self
     */
    public function setWixOrder($order)
    {        
        $this->wixOrder = $order;
        return $this;
    }
    public function getWixOrder()
    {
        return $this->wixOrder;
    }
    /**
     * 
     */
    public function setShippingAddress($address)
    {        
        $this->shippingAddress = $address;
        return $this;
    }
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }
    /**
     * 
     */
    public function setOrderProducts($products)
    {
        $this->orderProducts = $products;
        return $this;
    }
    public function getOrderProducts()
    {        
        return $this->orderProducts;

    }
    
    /**
     * 
     */
    public function getRequest()
    {
        return $this->request;
    }

}