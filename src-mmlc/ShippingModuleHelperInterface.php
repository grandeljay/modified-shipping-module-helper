<?php

/**
 * Shipping Module Helper
 *
 * @version 0.1.0
 * @author  Jay Trees <shipping-module-helper@grandels.email>
 * @link    https://github.com/grandeljay/modified-shipping-module-helper
 */

namespace Grandeljay\ShippingModuleHelper;

interface ShippingModuleHelperInterface
{
    /**
     * Returns the current shipping weight. Unlike `$total_weight`, this method
     * will consider volumetric weight.
     *
     * @return float
     */
    public function getShippingWeight(): float;
}
