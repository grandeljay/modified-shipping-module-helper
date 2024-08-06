<?php

namespace Grandeljay\ShippingModuleHelper;

class OrderPacker
{
    private float $weight_ideal;
    private float $weight_maximum;

    private array $boxes = [];

    public function __construct()
    {
    }

    public function setIdealWeight(float $ideal_weight): void
    {
        $this->weight_ideal = $ideal_weight;
    }

    public function setMaximumWeight(float $maximum_weight): void
    {
        $this->weight_maximum = $maximum_weight;
    }

    private function getOrderProducts(): array
    {
        global $order;

        if (!isset($order->products) || !\is_iterable($order->products)) {
            return [];
        }

        return $order->products;
    }

    /**
     * Places all products and their attributes into boxes for shipping.
     *
     * @return void
     */
    public function packOrder(): void
    {
        $order_products = $this->getOrderProducts();

        foreach ($order_products as $order_product_data) {
            /** Product */
            $order_product = new OrderProduct($order_product_data);

            $this->packProduct($order_product);

            /** Product Attributes */
            $order_product_attributes_data = $order_product->getAttributesAsProductData();

            foreach ($order_product_attributes_data as $attribute_as_product_data) {
                $attribute_order_product = new OrderProduct($attribute_as_product_data);

                $this->packProduct($attribute_order_product);
            }
        }
    }

    /**
     * Packs a product into a suitable box. Ideal and maximum weight are
     * considered.
     *
     * @param OrderProduct $order_product The product to pack.
     *
     * @return bool Whether a suitable box was found for the product.
     */
    public function packProduct(OrderProduct $order_product): bool
    {
        $boxes_to_consider = $this->getBoxesToConsider($order_product);

        $box_weight_maximum = $this->weight_maximum;
        $box_weight_ideal   = $this->weight_ideal;

        $product_weight             = $order_product->getWeightWithoutAttributes();
        $product_quantity           = $order_product->getQuantity();
        $product_quantity_remaining = $product_quantity;

        /** Pack into existing boxes */
        foreach ($boxes_to_consider as $order_box) {
            $product_quantity_to_add = $this->getQuantityToAdd($order_box, $order_product, $product_quantity_remaining);

            $order_box->addProductWithAttributes($order_product, $product_quantity_to_add);

            $product_quantity_remaining -= $product_quantity_to_add;

            if ($product_quantity_remaining <= 0) {
                /** The product was packed, there is nothing more to do. */
                return true;
            }
        }

        /** Pack into new boxes */
        if ($product_weight <= 0) {
            $product_quantity_possible = $product_quantity_remaining;
        } elseif ($product_weight > $box_weight_ideal) {
            $product_quantity_possible = 1;
        } else {
            $product_quantity_possible = \min(
                $product_quantity_remaining,
                \floor($box_weight_ideal / $product_weight)
            );
        }

        do {
            $product_quantity_to_add = \min(
                $product_quantity_remaining,
                $product_quantity_possible
            );

            $box_to_add = new OrderBox();
            $box_to_add->addProductWithAttributes($order_product, $product_quantity_to_add);

            $this->boxes[] = $box_to_add;

            $product_quantity_remaining -= $product_quantity_to_add;
        } while ($product_quantity_remaining > 0);

        return true;
    }

    public function getBoxes(): array
    {
        return $this->boxes;
    }

    private function getQuantityToAdd(OrderBox $order_box, OrderProduct $order_product, int $product_quantity_remaining): int
    {
        $box_weight           = $order_box->getWeightWithoutAttributes();
        $box_weight_ideal     = $this->weight_ideal;
        $box_weight_remaining = $box_weight_ideal - $box_weight;

        $product_weight   = $order_product->getWeightWithoutAttributes();
        $product_quantity = $order_product->getQuantity();

        if ($product_weight <= 0) {
            $product_quantity_possible = $product_quantity;
        } elseif ($product_weight > $box_weight_ideal) {
            $product_quantity_possible = 1;
        } else {
            $product_quantity_possible = \min(
                $product_quantity,
                \floor($box_weight_remaining / $product_weight)
            );
        }

        $product_quantity_to_add = \min(
            $product_quantity_remaining,
            $product_quantity_possible
        );

        return $product_quantity_to_add;
    }

    private function getBoxesToConsider(OrderProduct $order_product): array
    {
        $boxes_to_consider = [];

        $box_weight_ideal = $this->weight_ideal;

        $product_weight = $order_product->getWeightWithoutAttributes();

        foreach ($this->boxes as $box) {
            $box_weight           = $box->getWeightWithoutAttributes();
            $box_weight_remaining = $box_weight_ideal - $box_weight;
            $box_is_full          = $box_weight >= $box_weight_ideal;

            $product_fits_in_box = $box_weight + $product_weight <= $box_weight_ideal;

            if ($box_weight_remaining <= 0 || $box_is_full || !$product_fits_in_box) {
                continue;
            }

            $boxes_to_consider[] = $box;
        }

        return $boxes_to_consider;
    }

    public function getWeight(): float
    {
        $weight           = 0;
        $weight_empty_box = defined('SHIPPING_BOX_WEIGHT') ? (float) \SHIPPING_BOX_WEIGHT : 0;

        foreach ($this->boxes as $box) {
            $weight += $box->getWeightWithoutAttributes();
            $weight += $weight_empty_box;
        }

        return $weight;
    }

    public function getWeightFormatted(): string
    {
        $weight              = $this->getWeight();
        $weight_formatted    = \sprintf('%01.2f Kg', $weight);
        $weight_per_box      = [];
        $weight_per_box_text = [];

        $user_is_admin = isset($_SESSION['customers_status']['customers_status_id']) && 0 === (int) $_SESSION['customers_status']['customers_status_id'];

        if ($user_is_admin) {
            foreach ($this->boxes as $box) {
                $box_weight = $box->getWeightWithoutAttributes();
                $box_key    = \sprintf(
                    '%01.2f Kg',
                    $box_weight
                );

                if (isset($weight_per_box[$box_key])) {
                    $weight_per_box[$box_key]++;
                } else {
                    $weight_per_box[$box_key] = 1;
                }
            }

            foreach ($weight_per_box as $box_key => $box_count) {
                $weight_per_box_text[] = \sprintf(
                    '%dx %s',
                    $box_count,
                    $box_key
                );
            }

            $weight_formatted = \implode(', ', $weight_per_box_text);
        }

        return $weight_formatted;
    }
}
