<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;

trait CatalogProducts
{
    /**
     * Create a product.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/catalog-products/v1/#products_create
     */
    public function createProduct(array $data)
    {
        $this->apiEndPoint = 'v1/catalogs/products';

        $this->options['json'] = $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * List products.
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/catalog-products/v1/#products_list
     */
    public function listProducts()
    {
        $this->apiEndPoint = "v1/catalogs/products?page={$this->current_page}&page_size={$this->page_size}&total_required={$this->show_totals}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * Update a product.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/catalog-products/v1/#products_patch
     */
    public function updateProduct(string $product_id, array $data)
    {
        $this->apiEndPoint = "v1/catalogs/products/{$product_id}";

        $this->options['json'] = $data;

        $this->verb = 'patch';

        return $this->doPaypalRequest(false);
    }

    /**
     * Get product details.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/catalog-products/v1/#products_get
     */
    public function showProductDetails(string $product_id)
    {
        $this->apiEndPoint = "v1/catalogs/products/{$product_id}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }
}
