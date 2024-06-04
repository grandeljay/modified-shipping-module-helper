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
     * @param  OrderProduct $order_product The product to pack.
     *
     * @return bool Whether a suitable box was found for the product.
     */
    public function packProduct(OrderProduct $order_product): bool
    {
        $product_weight_to_pack = $order_product->getWeightWithoutAttributes();

        $box_weight_maximum = $this->weight_maximum;
        $box_weight_ideal   = $this->weight_ideal;

        for ($quantity = 1; $quantity <= $order_product->getQuantity(); $quantity++) {
            $product_was_packed = false;

            foreach ($this->boxes as $box) {
                $box_weight = $box->getWeightWithoutAttributes();

                $product_fits_in_box = $box_weight + $product_weight_to_pack <= $box_weight_ideal;

                if ($product_fits_in_box) {
                    $box->addProductWithAttributes($order_product);

                    $product_was_packed = true;
                } else {
                    continue;
                }
            }

            if (true !== $product_was_packed) {
                $box_to_add = new OrderBox();
                $box_to_add->addProductWithAttributes($order_product);

                $this->boxes[] = $box_to_add;

                $product_was_packed = true;
            }
        }

        return $product_was_packed;
    }

    public function getBoxes(): array
    {
        return $this->boxes;
    }

    public function getWeight(): float
    {
        $weight = 0;

        foreach ($this->boxes as $box) {
            $weight += $box->getWeightWithoutAttributes();
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
