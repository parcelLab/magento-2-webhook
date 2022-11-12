<?php

namespace Mageplaza\Webhook\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Zend_Db_Exception;

use Mageplaza\Webhook\Model\HookFactory;

class InstallData implements InstallDataInterface
{
    protected $hookFactory;

    public function __construct(
        HookFactory $hookFactory
    ) {
        $this->hookFactory = $hookFactory;
    }

	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$defaultOrderHook = [
			'name' => "parcelLab Transfer Webhook on Event order",
            'status' => 0,
            'store_ids' => '0',
            'hook_type' => 'order',
            'order_status' => 'pending,fraud,complete,closed,canceled,holded',
            'priority' => 10,
            'payload_url' => 'https://api.parcellab.com/magento2/order',
            'method' => 'POST',
            'authentication' => 'basic',
            'headers' => '[]',
            'content_type' => 'application/json',
            'body' => "{\n\"state\": \"{{ item.state }}\",\n\"entity_id\": \"{{ item.entity_id }}\",\n\"order_id\": \"{{ item.order_id }}\",\n\"ext_order_id\": \"{{ item.ext_order_id }}\",\n\"increment_id\": \"{{ item.increment_id }}\",\n\"customer_id\": \"{{ item.customer_id }}\",\n\"ext_customer_id\": \"{{ item.ext_customer_id }}\",\n\"store_id\": \"{{ item.store_id }}\",\n\"created_at\": \"{{ item.created_at }}\",\n\"customer_is_guest\": \"{{ item.customer_is_guest }}\",\n\"weight\": \"{{ item.weight }}\",\n\n\"items\": [\n{% for product in item.items -%}\n{%- if forloop.length > 0 -%}\n{\n  \"name\": \"{{ product.name | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n  \"sku\": \"{{ product.sku | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n  \"qty_ordered\": \"{{ product.qty_ordered }}\"\n}\n{% unless forloop.last %},{% endunless -%}\n{%- endif -%}\n{% endfor %}\n],\n\n\"shippingAddress\": \n{\n\"firstname\": \"{{ item.shippingAddress.firstname | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"lastname\": \"{{ item.shippingAddress.lastname | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"email\": \"{{ item.shippingAddress.email }}\",\n\"telephone\": \"{{ item.shippingAddress.telephone | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"region\": \"{{ item.shippingAddress.region | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"street\": \"{{ item.shippingAddress.street | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"city\": \"{{ item.shippingAddress.city | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"postcode\": \"{{ item.shippingAddress.postcode | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"country_id\": \"{{ item.shippingAddress.country_id }}\"\n},\n\n\"billingAddress\": \n{\n\"firstname\": \"{{ item.billingAddress.firstname | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"lastname\": \"{{ item.billingAddress.lastname | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"email\": \"{{ item.billingAddress.email }}\",\n\"telephone\": \"{{ item.billingAddress.telephone | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"street\": \"{{ item.billingAddress.street | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"region\": \"{{ item.billingAddress.region | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"city\": \"{{ item.billingAddress.city | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"postcode\":  \"{{ item.billingAddress.postcode | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"country_id\": \"{{ item.billingAddress.country_id }}\"\n}\n\n}",
		];

        $hooks = $this->hookFactory->create();
        $hooks->addData($defaultOrderHook)->save();

		$defaultNewShipmentHook = [
			'name' => "parcelLab Transfer Webhook on Event new_shipment",
            'status' => 0,
            'store_ids' => '0',
            'hook_type' => 'new_shipment',
            'priority' => 10,
            'payload_url' => 'https://api.parcellab.com/magento2/new_shipment',
            'method' => 'POST',
            'authentication' => 'basic',
            'headers' => '[]',
            'content_type' => 'application/json',
            'body' => "{\n\"state\": \"{{ item.state }}\",\n\"entity_id\": \"{{ item.entity_id }}\",\n\"order_id\": \"{{ item.order_id }}\",\n\"ext_order_id\": \"{{ item.ext_order_id }}\",\n\"increment_id\": \"{{ item.increment_id }}\",\n\"customer_id\": \"{{ item.customer_id }}\",\n\"ext_customer_id\": \"{{ item.ext_customer_id }}\",\n\"store_id\": \"{{ item.store_id }}\",\n\"created_at\": \"{{ item.created_at }}\",\n\"customer_is_guest\": \"{{ item.customer_is_guest }}\",\n\"weight\": \"{{ item.weight }}\",\n\n\"shipment_status\": \"{{ item.shipment_status }}\",\n\"shipping_label\": \"{{ item.shipping_label }}\",\n\"tracksCollection\": [\n{% for track in item.tracksCollection -%}\n{%- if forloop.length > 0 -%}\n{\n  \"title\": \"{{ track.title | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n  \"track_number\": \"{{ track.track_number | replace: '\"', '%22%' | replace: '\\', '' | strip }}\"\n}\n{% unless forloop.last %},{% endunless -%}\n{%- endif -%}\n{% endfor %}\n],\n\n\n\"items\": [\n{% for product in item.items -%}\n{%- if forloop.length > 0 -%}\n{\n  \"name\": \"{{ product.name | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n  \"sku\": \"{{ product.sku | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n  \"qty\": \"{{ product.qty }}\"\n}\n{% unless forloop.last %},{% endunless -%}\n{%- endif -%}\n{% endfor %}\n],\n\n\"shippingAddress\": \n{\n\"firstname\": \"{{ item.shippingAddress.firstname | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"lastname\": \"{{ item.shippingAddress.lastname | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"email\": \"{{ item.shippingAddress.email }}\",\n\"telephone\": \"{{ item.shippingAddress.telephone | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"region\": \"{{ item.shippingAddress.region | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"street\": \"{{ item.shippingAddress.street | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"city\": \"{{ item.shippingAddress.city | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"postcode\": \"{{ item.shippingAddress.postcode | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"country_id\": \"{{ item.shippingAddress.country_id }}\"\n},\n\n\"billingAddress\": \n{\n\"firstname\": \"{{ item.billingAddress.firstname | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"lastname\": \"{{ item.billingAddress.lastname | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"email\": \"{{ item.billingAddress.email }}\",\n\"telephone\": \"{{ item.billingAddress.telephone | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"street\": \"{{ item.billingAddress.street | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"region\": \"{{ item.billingAddress.region | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"city\": \"{{ item.billingAddress.city | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"postcode\":  \"{{ item.billingAddress.postcode | strip_html | replace: '\"', '%22%' | replace: '\\', '' | strip }}\",\n\"country_id\": \"{{ item.billingAddress.country_id }}\"\n}\n\n}",
		];
        
        $hooks = $this->hookFactory->create();
        $hooks->addData($defaultNewShipmentHook)->save();
	}
}