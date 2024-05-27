<?php

namespace Grandeljay\ShippingModuleHelper;

class OrderBox
{
    private array $products = [];

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

    public function getWeightWithAttributes(): float
    {
        $weight = 0;

        foreach ($this->products as $order_product) {
            /**
             * Product attributes are put into the box seperately. In order to
             * avoid duplicate calculation of the product attributes weight,
             * it's being returned without the attributes' weight here.
             */
            $weight += $order_product->getWeightWithoutAttributes();
        }

        return $weight;
    }

    public function isEmpty(): bool
    {
        return empty($this->products);
    }
}
