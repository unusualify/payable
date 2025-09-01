<?php

namespace Unusualify\Payable\Contracts;

/**
 * @property protected static bool $hasBuiltInForm
 */
interface ShouldEmbedForm
{
    /**
     * Get the attributes for the built-in form
     * @param array $payload
     * @return array
     */
    public function getBuiltInFormAttributes(array $payload): array;

    /**
     * Validate the checkout payload
     * @param array $payload
     * @return array
     */
    public function validateCheckoutPayload(array $payload);

    /**
     * Hydrate the checkout payload
     * @param array $payload
     * @return array
     */
    public function hydrateCheckoutPayload(array $payload): array;
}
