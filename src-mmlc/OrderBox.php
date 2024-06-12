<?php

namespace Grandeljay\ShippingModuleHelper;

class OrderBox
{
    private array $products = [];

    public function __construct()
    {
    }

    public function addProductWithAttributes(OrderProduct $order_product, int $quantity = 1): void
    {
        $product_was_added = false;

        foreach ($this->products as &$product_entry) {
            if ($product_entry['product'] === $order_product) {
                $product_entry['quantity'] += $quantity;
                $product_was_added          = true;

                break;
            }
        }

        if (!$product_was_added) {
            $this->products[] = [
                'quantity' => $quantity,
                'product'  => $order_product,
            ];
        }
    }

    public function addAttribute(array $attribute): void
    {
        $this->products[] = $attribute;
    }

    /**
     * @deprecated 0.1.2 This method was renamed to `getWeightWithoutAttributes`.
     *
     * @return float
     */
    public function getWeightWithAttributes(): float
    {
        return $this->getWeightWithoutAttributes();
    }

    public function getWeightWithoutAttributes(): float
    {
        $weight = 0;

        foreach ($this->products as $product_entry) {
            /**
             * Product attributes are put into the box seperately. In order to
             * avoid duplicate calculation of the product attributes weight,
             * it's being returned without the attributes' weight here.
             */
            $order_product          = $product_entry['product'];
            $order_product_quantity = $product_entry['quantity'];

            $weight += $order_product->getWeightWithoutAttributes() * $order_product_quantity;
        }

        return $weight;
    }

    public function isEmpty(): bool
    {
        return empty($this->products);
    }
}
