<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\Request;

/**
 * Class PaywallService
 *
 * Provides the base structure for interacting with external payment services.
 */
abstract class PaywallService
{
    use ConsumesExternalServices;

    /**
     * The base URI for the external payment service.
     *
     * @var string
     */
    protected string $baseUri;

    /**
     * Resolves the authorization for the external service request.
     *
     * @param array $queryParams The query parameters for the request.
     * @param array $formParams The form parameters for the request.
     * @param array $headers The headers for the request.
     * @return void
     */
    abstract protected function resolveAuthorization(&$queryParams, &$formParams, &$headers): void;

    /**
     * Decodes the response from the external service.
     *
     * @param mixed $response The response from the external service.
     * @return mixed The decoded response.
     */
    abstract protected function decodeResponse($response);

    /**
     * Generates a payment link for the given request.
     *
     * @param Request $request The HTTP request containing the payment details.
     * @param string $returnUrl The URL that should be called after payment proceeded.
     * @param string $cancelUrl The URL that should be called after payment cancelled or failed.
     * @return string The generated payment link.
     */
    abstract public function getPaymentLink(Request $request, string $returnUrl, string $cancelUrl): string;

    /**
     * Handles the approval process for the payment.
     *
     * @return array The result of the approval process.
     */
    abstract public function handleApproval(): array;
}
