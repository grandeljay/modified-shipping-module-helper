<?php

namespace Grandeljay\ShippingModuleHelper;

class OrderBox
{
    private array $products = [];
    private float $weight;

    public function __construct()
    {
    }

    public function addProductWithAttributes(OrderProduct $order_product): void
    {
        $this->products[] = $order_product;
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
        if (isset($this->weight)) {
            return $this->weight;
        }

        $weight = 0;

        foreach ($this->products as $order_product) {
            /**
             * Product attributes are put into the box seperately. In order to
             * avoid duplicate calculation of the product attributes weight,
             * it's being returned without the attributes' weight here.
             */
            $weight += $order_product->getWeightWithoutAttributes();
        }

        $this->weight = $weight;

        return $weight;
    }

    public function isEmpty(): bool
    {
        return empty($this->products);
    }
}
