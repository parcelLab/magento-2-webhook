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

namespace Mageplaza\Webhook\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class HookType
 * @package Mageplaza\Webhook\Model\Config\Source
 */
class HookType implements ArrayInterface
{
    const ORDER = 'order';
    const NEW_ORDER_COMMENT = 'new_order_comment';
    const NEW_SHIPMENT = 'new_shipment';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->toArray() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::ORDER => 'Order',
            self::NEW_ORDER_COMMENT => 'New Order Comment',
            self::NEW_SHIPMENT => 'New Shipment',
        ];
    }
}
