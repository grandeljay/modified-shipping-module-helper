<?php

/**
 * Shipping Module Helper
 *
 * @version 0.1.0
 * @author  Jay Trees <shipping-module-helper@grandels.email>
 * @link    https://github.com/grandeljay/modified-shipping-module-helper
 */

namespace Grandeljay\ShippingModuleHelper;

class TestModule extends ShippingModuleHelper
{
    public function __construct()
    {
        $shipping_weight = $this->getShippingWeight();
    }
}
