<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Webhook
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Webhook\Helper;

use Exception;
use Liquid\Template;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData as CoreHelper;
use Mageplaza\Webhook\Block\Adminhtml\LiquidFilters;
use Mageplaza\Webhook\Model\Config\Source\Authentication;
use Mageplaza\Webhook\Model\Config\Source\HookType;
use Mageplaza\Webhook\Model\Config\Source\Schedule;
use Mageplaza\Webhook\Model\Config\Source\Status;
use Mageplaza\Webhook\Model\HistoryFactory;
use Mageplaza\Webhook\Model\HookFactory;
use Mageplaza\Webhook\Model\ResourceModel\Hook\Collection;
use Laminas\Http\Response;

/**
 * Class Data
 * @package Mageplaza\Webhook\Helper
 */
class Data extends CoreHelper
{
    const CONFIG_MODULE_PATH = 'mp_webhook';

    /**
     * @var LiquidFilters
     */
    protected $liquidFilters;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var HookFactory
     */
    protected $hookFactory;

    /**
     * @var HistoryFactory
     */
    protected $historyFactory;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customer;

    /**
     * @var Configurable $configurable
     * */
    protected $configurable;

    /**
     * @var ProductFactory $productFactory
     * */
    protected $productFactory;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $backendUrl
     * @param TransportBuilder $transportBuilder
     * @param CurlFactory $curlFactory
     * @param LiquidFilters $liquidFilters
     * @param HookFactory $hookFactory
     * @param HistoryFactory $historyFactory
     * @param CustomerRepositoryInterface $customer
     * @param Configurable $configurable
     * @param ProductFactory $productFactory
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        UrlInterface $backendUrl,
        TransportBuilder $transportBuilder,
        CurlFactory $curlFactory,
        LiquidFilters $liquidFilters,
        HookFactory $hookFactory,
        HistoryFactory $historyFactory,
        CustomerRepositoryInterface $customer,
        Configurable $configurable,
        ProductFactory $productFactory
    ) {
        $this->liquidFilters    = $liquidFilters;
        $this->curlFactory      = $curlFactory;
        $this->hookFactory      = $hookFactory;
        $this->historyFactory   = $historyFactory;
        $this->transportBuilder = $transportBuilder;
        $this->backendUrl       = $backendUrl;
        $this->customer         = $customer;
        $this->configurable     = $configurable;
        $this->productFactory   = $productFactory;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param $item
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getItemStore($item)
    {
        if (method_exists($item, 'getData')) {
            return $item->getData('store_id') ?: $this->storeManager->getStore()->getId();
        }

        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param $item
     * @param $hookType
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function send($item, $hookType)
    {
        if (!$this->isEnabled()) {
            return;
        }

        /** @var Collection $hookCollection */
        $hookCollection = $this->hookFactory->create()->getCollection()
            ->addFieldToFilter('hook_type', $hookType)
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter('store_ids', [
                ['finset' => Store::DEFAULT_STORE_ID],
                ['finset' => $this->getItemStore($item)]
            ])
            ->setOrder('priority', 'ASC');

        $isSendMail     = $this->getConfigGeneral('alert_enabled');
        $sendTo         = explode(',', 'support@parcellab.com');
        try {
            $sendTo     = explode(',', $this->getConfigGeneral('send_to'));
        } catch (Exception $e) { }

        foreach ($hookCollection as $hook) {
            if ($hook->getHookType() === HookType::ORDER) {
                $statusItem  = $item->getStatus();
                $orderStatus = explode(',', $hook->getOrderStatus());
                if (!in_array($statusItem, $orderStatus, true)) {
                    continue;
                }
            }
            $history = $this->historyFactory->create();
            $data    = [
                'hook_id'     => $hook->getId(),
                'hook_name'   => $hook->getName(),
                'store_ids'   => $hook->getStoreIds(),
                'hook_type'   => $hook->getHookType(),
                'priority'    => $hook->getPriority(),
                'payload_url' => $this->generateLiquidTemplate($item, $hook->getPayloadUrl()),
                'body'        => $this->generateItemJsonString($item)
            ];
            $history->addData($data);
            try {
                $result = $this->sendHttpRequestFromHook($hook, $item);
                $history->setResponse(isset($result['response']) ? $result['response'] : '');
            } catch (Exception $e) {
                $result = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
            if ($result['success'] === true) {
                $history->setStatus(Status::SUCCESS);
            } else {
                $history->setStatus(Status::ERROR)
                    ->setMessage($result['message']);
                if ($isSendMail) {
                    $this->sendMail(
                        $sendTo,
                        __('Something went wrong while sending %1 hook', $hook->getName()),
                        $this->getConfigGeneral('email_template'),
                        $this->getStoreId()
                    );
                }
            }

            $history->save();
        }
    }

    /**
     * @param $hook
     * @param bool $item
     * @param bool $log
     *
     * @return array
     */
    public function sendHttpRequestFromHook($hook, $item = false, $log = false)
    {
        $url            = $log ? $log->getPayloadUrl() : $this->generateLiquidTemplate($item, $hook->getPayloadUrl());
        $authentication = $hook->getAuthentication();
        $method         = $hook->getMethod();
        $username       = $hook->getUsername();
        $password       = $hook->getPassword();

        $globalUsername = $this->getConfigGeneral('parcellab_user_id');
        $globalPassword = $this->getConfigGeneral('parcellab_api_token');

        $authentication = $this->getBasicAuthHeader($globalUsername, $globalPassword);

        $body        = $log ? $log->getBody() : $this->generateItemJsonString($item);
        $headers     = $hook->getHeaders();
        $contentType = $hook->getContentType();

        return $this->sendHttpRequest($headers, $authentication, $contentType, $url, $body, $method);
    }

    public function generateItemJsonString($item)
    {
        $result = [
            "state" => $item->getState(),
            "entity_id" => $item->getEntityId(),
            "order_id" => $item->getOrderId(),
            "ext_order_id" => $item->getExtOrderId(),
            "increment_id" => $item->getIncrementId(),
            "customer_id" => $item->getCustomerId(),
            "ext_customer_id" => $item->getExtCustomerId(),
            "store_id" => $item->getStoreId(),
            "created_at" => $item->getCreatedAt(),
            "customer_is_guest" => $item->getCustomerIsGuest(),
            "weight" => $item->getWeight(),
            "shipping_description" => $item->getShippingDescription(),
            "shipment_status" => $item->getShipmentStatus(),
            "shipping_label" => $item->getShippingLabel(),
        ];

        if ($item->getShippingAddress()) {
            $shippingAddressData = $item->getShippingAddress();
            $result["shippingAddress"] = [
                "company" => $shippingAddressData->getCompany(),
                "firstname" => $shippingAddressData->getFirstname(),
                "lastname" => $shippingAddressData->getLastname(),
                "email" => $shippingAddressData->getEmail(),
                "telephone" => $shippingAddressData->getTelephone(),
                "region" => $shippingAddressData->getRegion(),
                "street" => $shippingAddressData->getStreet()
                    ? join("<br />", $shippingAddressData->getStreet())
                    : "",
                "city" => $shippingAddressData->getCity(),
                "postcode" => $shippingAddressData->getPostcode(),
                "country_id" => $shippingAddressData->getCountryId(),
                "customs_code" => $shippingAddressData->getCustomsCode(),
            ];
        }

        if ($item->getBillingAddress()) {
            $billingAddressData = $item->getBillingAddress();
            $result["billingAddress"] = [
                "company" => $billingAddressData->getCompany(),
                "firstname" => $billingAddressData->getFirstname(),
                "lastname" => $billingAddressData->getLastname(),
                "email" => $billingAddressData->getEmail(),
                "telephone" => $billingAddressData->getTelephone(),
                "street" => $billingAddressData->getStreet()
                    ? join("<br />", $billingAddressData->getStreet())
                    : "",
                "region" => $billingAddressData->getRegion(),
                "city" => $billingAddressData->getCity(),
                "postcode" => $billingAddressData->getPostcode(),
                "country_id" => $billingAddressData->getCountryId(),
            ];
        }

        if ($item instanceof \Magento\Sales\Model\Order) {
            $result["storeUrl"] = $item->getStore()->getBaseUrl();
            $result["items"] = [];
            foreach ($item->getItems() as $orderItem) {
                /** @var Product $product */
                $product = $orderItem->getProduct();
                [$image, $productUrl] = $this->getProductUrls($product);
                $result["items"][] = [
                    "name" => $orderItem->getName(),
                    "sku" => $orderItem->getSku(),
                    "product_url" => $productUrl,
                    "image_url" => $image,
                    "product_type" => $product->getTypeId(),
                    "qty_ordered" => $orderItem->getQtyOrdered(),
                ];
            }
        }

        if ($item instanceof \Magento\Sales\Model\Order\Shipment) {
            $result["tracksCollection"] = [];
            if ($item->getTracksCollection()) {
                if ($item->getTracksCollection()->getItems()) {
                    $tracksCollection = $item->getTracksCollection()->getItems();
                    foreach ($tracksCollection as $track) {
                        $result["tracksCollection"][] = [
                            "title" => $track->getTitle(),
                            "track_number" => $track->getTrackNumber(),
                        ];
                    }
                }
            }

            $result["items"] = [];
            foreach ($item->getItemsCollection() as $orderItem) {
                $result["items"][] = [
                    "name" => $orderItem->getName(),
                    "sku" => $orderItem->getSku(),
                    "qty" => $orderItem->getQty(),
                ];
            }
        }

        return json_encode($result);
    }

    /**
     * @param $item
     * @param $templateHtml
     *
     * @return string
     */
    public function generateLiquidTemplate($item, $templateHtml)
    {
        try {
            $template       = new Template;
            $filtersMethods = $this->liquidFilters->getFiltersMethods();

            $template->registerFilter($this->liquidFilters);
            $template->parse($templateHtml, $filtersMethods);

            if ($item instanceof Product) {
                $item->setStockItem(null);
            }

            if ($item instanceof \Magento\Sales\Model\Order) {
                $item->setData('storeUrl', $item->getStore()->getBaseUrl());

                foreach ($item->getItems() as $orderItem) {
                    /** @var Product $product */
                    $product = $orderItem->getProduct();
                    [$image, $productUrl] = $this->getProductUrls($product);

                    $orderItem->setData('image', $image);
                    $orderItem->setData('productUrl', $productUrl);
                }
            }

            if ($item->getShippingAddress()) {
                $item->setData('shippingAddress', $item->getShippingAddress()->getData());
            }

            if ($item->getBillingAddress()) {
                $item->setData('billingAddress', $item->getBillingAddress());
            }

            if ($item instanceof \Magento\Sales\Model\Order\Shipment) {
                if ($item->getTracksCollection()) {
                    if ($item->getTracksCollection()->getItems()) {
                        $tracksCollection = $item->getTracksCollection()->getItems();
                        $item->setData('tracksCollection', $tracksCollection);
                    }
                }
            }

            return $template->render([
                'item' => $item,
            ]);
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        return '';
    }

    /**
     * @param Product $product
     *
     * @return array
     * */
    private function getProductUrls($product){

        if($product->getTypeId() === Configurable::TYPE_CODE){
            // if product is a configurable product
            $targetProduct = $product;
        }else{
            $parentIds = $this->configurable->getParentIdsByChild($product->getId());
            if(count($parentIds) > 0){
                // if simple product has configurable product, get URL from configurable
                $configurable = $this->productFactory->create()->load($parentIds[0]);
                $targetProduct = $configurable;
            }else{
                // simple product without configurable
                $targetProduct = $product;
            }
        }

        $image = $targetProduct->getData('image');
        $productUrl = $targetProduct->getUrlKey();
        return [$image, $productUrl];
    }

    /**
     * @param $headers
     * @param $authentication
     * @param $contentType
     * @param $url
     * @param $body
     * @param $method
     *
     * @return array
     */
    public function sendHttpRequest($headers, $authentication, $contentType, $url, $body, $method)
    {
        if (!$method) {
            $method = 'GET';
        }
        if ($headers && !is_array($headers)) {
            $headers = $this::jsonDecode($headers);
        }
        $headersConfig = [];

        $headersConfig[] = 'parcellab-magento-2-webhook: v2.4.23';
        $headersConfig[] = 'parcellab-payload-format: v2';

        foreach ($headers as $header) {
            $key             = $header['name'];
            $value           = $header['value'];
            $headersConfig[] = trim($key) . ': ' . trim($value);
        }

        if ($authentication) {
            $headersConfig[] = 'Authorization: ' . $authentication;
        }

        if ($contentType) {
            $headersConfig[] = 'Content-Type: ' . $contentType;
        }

        $curl = $this->curlFactory->create();
        $curl->write($method, $url, '1.1', $headersConfig, $body);

        $result = ['success' => false];

        try {
            $resultCurl         = $curl->read();
            $result['response'] = $resultCurl;
            if (!empty($resultCurl)) {
                $resultObject = Response::fromString($resultCurl);
                $result['status'] = $resultObject->getStatusCode();
                if ($resultObject->isSuccess()) {
                    $result['success'] = true;
                } else {
                    $result['message'] = __('Cannot connect to server. Please try again later.');
                }
            } else {
                $result['message'] = __('Cannot connect to server. Please try again later.');
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }
        $curl->close();

        return $result;
    }

    /**
     * @param $url
     * @param $method
     * @param $username
     * @param $realm
     * @param $password
     * @param $nonce
     * @param $algorithm
     * @param $qop
     * @param $nonceCount
     * @param $clientNonce
     * @param $opaque
     *
     * @return string
     */
    public function getDigestAuthHeader(
        $url,
        $method,
        $username,
        $realm,
        $password,
        $nonce,
        $algorithm,
        $qop,
        $nonceCount,
        $clientNonce,
        $opaque
    ) {
        $uri          = parse_url($url)[2];
        $method       = $method ?: 'GET';
        $A1           = hash('md5', "{$username}:{$realm}:{$password}");
        $A2           = hash('md5', "{$method}:{$uri}");
        $response     = hash('md5', "{$A1}:{$nonce}:{$nonceCount}:{$clientNonce}:{$qop}:${A2}");
        $digestHeader = "Digest username=\"{$username}\", realm=\"{$realm}\", nonce=\"{$nonce}\", uri=\"{$uri}\", cnonce=\"{$clientNonce}\", nc={$nonceCount}, qop=\"{$qop}\", response=\"{$response}\", opaque=\"{$opaque}\", algorithm=\"{$algorithm}\"";

        return $digestHeader;
    }

    /**
     * @param $username
     * @param $password
     *
     * @return string
     */
    public function getBasicAuthHeader($username, $password)
    {
        return 'Basic ' . base64_encode("{$username}:{$password}");
    }

    /**
     * @param $item
     * @param $hookType
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sendObserver($item, $hookType)
    {
        if (!$this->isEnabled()) {
            return;
        }

        /** @var Collection $hookCollection */
        $hookCollection = $this->hookFactory->create()->getCollection()
            ->addFieldToFilter('hook_type', $hookType)
            ->addFieldToFilter('status', 1)
            ->setOrder('priority', 'ASC');

        $isSendMail = $this->getConfigGeneral('alert_enabled');
        $sendTo     = explode(',', $this->getConfigGeneral('send_to'));

        foreach ($hookCollection as $hook) {
            try {
                $history = $this->historyFactory->create();
                $data    = [
                    'hook_id'     => $hook->getId(),
                    'hook_name'   => $hook->getName(),
                    'store_ids'   => $hook->getStoreIds(),
                    'hook_type'   => $hook->getHookType(),
                    'priority'    => $hook->getPriority(),
                    'payload_url' => $this->generateLiquidTemplate($item, $hook->getPayloadUrl()),
                    'body'        => $this->generateItemJsonString($item)
                ];
                $history->addData($data);
                try {
                    $result = $this->sendHttpRequestFromHook($hook, $item);
                    $history->setResponse(isset($result['response']) ? $result['response'] : '');
                } catch (Exception $e) {
                    $result = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                if ($result['success'] === true) {
                    $history->setStatus(Status::SUCCESS);
                } else {
                    $history->setStatus(Status::ERROR)
                        ->setMessage($result['message']);
                    if ($isSendMail) {
                        $this->sendMail(
                            $sendTo,
                            __('Something went wrong while sending %1 hook', $hook->getName()),
                            $this->getConfigGeneral('email_template'),
                            $this->storeManager->getStore()->getId()
                        );
                    }
                }
                $history->save();
            } catch (Exception $e) {
                if ($isSendMail) {
                    $this->sendMail(
                        $sendTo,
                        __('Something went wrong while sending %1 hook', $hook->getName()),
                        $this->getConfigGeneral('email_template'),
                        $this->storeManager->getStore()->getId()
                    );
                }
            }
        }
    }

    /**
     * @param $sendTo
     * @param $mes
     * @param $emailTemplate
     * @param $storeId
     *
     * @return bool
     * @throws LocalizedException
     */
    public function sendMail($sendTo, $mes, $emailTemplate, $storeId)
    {
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions([
                    'area'  => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars([
                    'viewLogUrl' => $this->backendUrl->getUrl('mpwebhook/logs/'),
                    'mes'        => $mes
                ])
                ->setFrom('general')
                ->addTo($sendTo);
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

            return true;
        } catch (MailException $e) {
            $this->_logger->critical($e->getLogMessage());
        }

        return false;
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param string $schedule
     * @param string $startTime
     *
     * @return string
     */
    public function getCronExpr($schedule, $startTime)
    {
        $ArTime        = explode(',', $startTime);
        $cronExprArray = [
            (int) $ArTime[1], // Minute
            (int) $ArTime[0], // Hour
            $schedule === Schedule::CRON_MONTHLY ? 1 : '*', // Day of the Month
            '*', // Month of the Year
            $schedule === Schedule::CRON_WEEKLY ? 0 : '*', // Day of the Week
        ];
        if ($schedule === Schedule::CRON_MINUTE) {
            return '* * * * *';
        }

        return implode(' ', $cronExprArray);
    }

    /**
     * @param null $field
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getCronSchedule($field = null)
    {
        $storeId = $this->getStoreId();
        if ($field === null) {
            return $this->getModuleConfig('cron/schedule', $storeId);
        }

        return $this->getModuleConfig('cron/' . $field, $storeId);
    }

    /**
     * @param $classPath
     *
     * @return mixed
     */
    public function getObjectClass($classPath)
    {
        return $this->objectManager->create($classPath);
    }

    /**
     * @param $code
     *
     * @return bool
     */
    public function isSuccess($code)
    {
        return (200 <= $code && 300 > $code);
    }
}
