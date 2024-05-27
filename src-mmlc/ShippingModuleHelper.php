<?php

/**
 * Shipping Module Helper
 *
 * @version 0.1.0
 * @author  Jay Trees <shipping-module-helper@grandels.email>
 * @link    https://github.com/grandeljay/modified-shipping-module-helper
 */

namespace Grandeljay\ShippingModuleHelper;

use RobinTheHood\ModifiedStdModule\Classes\StdModule;

class ShippingModuleHelper extends StdModule implements ShippingModuleHelperInterface
{
    /**
     * @deprecated
     *
     * @return float
     */
    public function getShippingWeight(): float
    {
        global $order;

        if (!isset($order)) {
            return 0;
        }

        $shipping_weight = 0;

        foreach ($order->products as $product_data) {
            $quantity = $product_data['quantity'] ?? 1;
            $weight   = $product_data['weight']   ?? 0;

            /**
             * Get the product's measurements in order to determine the
             * volumetric weight. Requires the
             * `grandeljay/cao-product-measurements` module.
             *
             * @link https://module-loader.de/modules/grandeljay/cao-product-measurements
             */
            $length = $product_data['length'] ?? 0;
            $width  = $product_data['width']  ?? 0;
            $height = $product_data['height'] ?? 0;

            if ($length > 0 && $width > 0 && $height > 0) {
                $volumetric_weight = ($length * $width * $height) / 5000;

                if ($volumetric_weight > $weight) {
                    $weight = $volumetric_weight;
                }
            }

            $shipping_weight += $weight * $quantity;
        }

        return $shipping_weight;
    }
}
