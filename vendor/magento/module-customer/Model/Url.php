<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Customer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Customer url model
 *
 * @api
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Url
{
    /**
     * No-route url constants
     */
    private const XML_PATH_WEB_DEFAULT_NO_ROUTE = 'web/default/no_route';

    /**
     * Route for customer account login page
     */
    public const ROUTE_ACCOUNT_LOGIN = 'customer/account/login';

    /**
     * Config name for Redirect Customer to Account Dashboard after Logging in setting
     */
    public const XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD = 'customer/startup/redirect_dashboard';

    /**
     * Query param name for last url visited
     */
    public const REFERER_QUERY_PARAM_NAME = 'referer';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var EncoderInterface
     */
    protected $urlEncoder;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    private $urlDecoder;

    /**
     * @var \Magento\Framework\Url\HostChecker
     */
    private $hostChecker;

    /**
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param EncoderInterface $urlEncoder
     * @param \Magento\Framework\Url\DecoderInterface|null $urlDecoder
     * @param \Magento\Framework\Url\HostChecker|null $hostChecker
     */
    public function __construct(
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        UrlInterface $urlBuilder,
        EncoderInterface $urlEncoder,
        ?\Magento\Framework\Url\DecoderInterface $urlDecoder = null,
        ?\Magento\Framework\Url\HostChecker $hostChecker = null
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->urlEncoder = $urlEncoder;
        $this->urlDecoder = $urlDecoder ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Url\DecoderInterface::class);
        $this->hostChecker = $hostChecker ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Url\HostChecker::class);
    }

    /**
     * Retrieve customer login url
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->urlBuilder->getUrl(self::ROUTE_ACCOUNT_LOGIN, $this->getLoginUrlParams());
    }

    /**
     * Retrieve parameters of customer login url
     *
     * @return array
     */
    public function getLoginUrlParams()
    {
        $params = [];
        $referer = $this->getRequestReferrer();
        if (!$referer
            && !$this->scopeConfig->isSetFlag(
                self::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD,
                ScopeInterface::SCOPE_STORE
            )
            && !$this->customerSession->getNoReferer()
            && $this->request->isGet()
        ) {
            $refererUrl = $this->urlBuilder->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
            if (!$this->isNoRouteUrl($refererUrl)) {
                $referer = $this->urlEncoder->encode($refererUrl);
            }
        }

        if ($referer) {
            $params = [self::REFERER_QUERY_PARAM_NAME => $referer];
        }

        return $params;
    }

    /**
     * Retrieve customer login POST URL
     *
     * @return string
     */
    public function getLoginPostUrl()
    {
        $params = [];
        $referer = $this->getRequestReferrer();
        if ($referer) {
            $params = [
                self::REFERER_QUERY_PARAM_NAME => $referer,
            ];
        }
        return $this->urlBuilder->getUrl('customer/account/loginPost', $params);
    }

    /**
     * Retrieve customer logout url
     *
     * @return string
     */
    public function getLogoutUrl()
    {
        return $this->urlBuilder->getUrl('customer/account/logout');
    }

    /**
     * Retrieve customer dashboard url
     *
     * @return string
     */
    public function getDashboardUrl()
    {
        return $this->urlBuilder->getUrl('customer/account');
    }

    /**
     * Retrieve customer account page url
     *
     * @return string
     */
    public function getAccountUrl()
    {
        return $this->urlBuilder->getUrl('customer/account');
    }

    /**
     * Retrieve customer register form url
     *
     * @return string
     */
    public function getRegisterUrl()
    {
        return $this->urlBuilder->getUrl('customer/account/create');
    }

    /**
     * Retrieve customer register form post url
     *
     * @return string
     */
    public function getRegisterPostUrl()
    {
        return $this->urlBuilder->getUrl('customer/account/createpost');
    }

    /**
     * Retrieve customer account edit form url
     *
     * @return string
     */
    public function getEditUrl()
    {
        return $this->urlBuilder->getUrl('customer/account/edit');
    }

    /**
     * Retrieve customer edit POST URL
     *
     * @return string
     */
    public function getEditPostUrl()
    {
        return $this->urlBuilder->getUrl('customer/account/editpost');
    }

    /**
     * Retrieve url of forgot password page
     *
     * @return string
     */
    public function getForgotPasswordUrl()
    {
        return $this->urlBuilder->getUrl('customer/account/forgotpassword');
    }

    /**
     * Retrieve confirmation URL for Email
     *
     * @param string $email
     * @return string
     */
    public function getEmailConfirmationUrl($email = null)
    {
        return $this->urlBuilder->getUrl('customer/account/confirmation', ['_query' => ['email' => $email]]);
    }

    /**
     * Getting request referrer
     *
     * @return mixed|null
     */
    private function getRequestReferrer()
    {
        $referer = $this->request->getParam(self::REFERER_QUERY_PARAM_NAME);
        if ($referer && $this->hostChecker->isOwnOrigin($this->urlDecoder->decode($referer))) {
            return $referer;
        }
        return null;
    }

    /**
     * Check if Referrer url is no route url
     *
     * @param string $url
     * @return bool
     */
    private function isNoRouteUrl($url)
    {
        $defaultNoRouteUrl = $this->scopeConfig->getValue(
            self::XML_PATH_WEB_DEFAULT_NO_ROUTE,
            ScopeInterface::SCOPE_STORE
        );
        $noRouteUrl = $this->urlBuilder->getUrl($defaultNoRouteUrl);
        if (strpos($url, $noRouteUrl) !== false) {
            return true;
        }
        return false;
    }
}
