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
            'order_status' => 'pending,processing,fraud,complete,closed,canceled,holded',
            'priority' => 10,
            'payload_url' => 'https://api.parcellab.com/magento2/order',
            'method' => 'POST',
            'authentication' => 'basic',
            'headers' => '[]',
            'content_type' => 'application/json',
            'body' => "{}",
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
            'body' => "{}",
		];
        
        $hooks = $this->hookFactory->create();
        $hooks->addData($defaultNewShipmentHook)->save();
	}
}