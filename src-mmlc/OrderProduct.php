<?php

namespace Grandeljay\ShippingModuleHelper;

class OrderProduct
{
    public function __construct(private array $data)
    {
    }

    public function getQuantity(): int
    {
        return $this->data['qty'] ?? 1;
    }

    public function getAttributes(): array
    {
        return $this->data['attributes'] ?? [];
    }

    public function getAttributesAsProductData(): array
    {
        $attributes             = $this->getAttributes();
        $attributes_as_products = [];

        foreach ($attributes as $attribute) {
            $attribute_as_product_query = \xtc_db_query(
                \sprintf(
                    'SELECT *
                       FROM `%s`
                      WHERE `products_model` = "%s"
                      LIMIT 1',
                    \TABLE_PRODUCTS,
                    $attribute['model']
                )
            );
            $attribute_as_product_data  = \xtc_db_fetch_array($attribute_as_product_query);
            $attribute_as_product_info  = [];

            if (null === $attribute_as_product_data) {
                continue;
            }

            $attribute_as_product_id                     = $attribute_as_product_data['products_id'];
            $attribute_as_product                        = new \product($attribute_as_product_id);
            $attribute_as_product_data                   = $attribute_as_product->data;
            $attribute_as_product_data['products_price'] = $attribute['price'];

            foreach ($attribute_as_product_data as $key => $value) {
                $key_products = 'products_';

                if (\str_starts_with($key, $key_products)) {
                    $key_truncated = \substr($key, \mb_strlen($key_products));
                    $key           = $key_truncated;
                }

                $attribute_as_product_info[$key] = $value;
            }

            /**
             * CAO Product Measurements
             */
            $cao_product_measurements  = new \grandeljay_cao_product_measurements_shopping_cart();
            $attribute_as_product_data = $cao_product_measurements->get_products(
                $attribute_as_product_info,
                $attribute_as_product_data,
                []
            );
            /** */

            $attributes_as_products[] = $attribute_as_product_data;
        }

        return $attributes_as_products;
    }

    public function getVolumetricWeight(): float
    {
        $length = $this->data['length'] ?? 0;
        $width  = $this->data['width']  ?? 0;
        $height = $this->data['height'] ?? 0;

        if ($length > 0 && $width > 0 && $height > 0) {
            $volumetric_weight = ($length * $width * $height) / 5000;

            return $volumetric_weight;
        }

        return 0;
    }

    public function getWeightWithAttributes(): float
    {
        $weight = $this->data['weight'] ?? 0;

        return $weight;
    }

    public function getWeightWithoutAttributes(): float
    {
        \preg_match('/\d+/', $this->data['id'], $product_id_matches);

        $product_id = $product_id_matches[0] ?? null;

        if (null === $product_id) {
            return 0;
        }

        $product_weight_query = \xtc_db_query(
            \sprintf(
                'SELECT `products_weight`
                   FROM `%s`
                  WHERE `products_id` = %d',
                \TABLE_PRODUCTS,
                $product_id
            )
        );
        $product_weight_data  = \xtc_db_fetch_array($product_weight_query);
        $product_weight       = $product_weight_data['products_weight'] ?? 0;

        return $product_weight;
    }
}
