<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Traits\ConsumesExternalServices;
use App\Services\PaywallService;

/**
 * Class SampleService
 * @package App\Services
 *
 * Handles interactions with sample provider for payment processing.
 */
class SampleService extends PaywallService
{
    use ConsumesExternalServices;

    /**
     * The key for Sample Paywall API authentication.
     *
     * @var string
     */
    protected $key;

    /**
     * The secret for Sample Paywall API authentication.
     *
     * @var string
     */
    protected $secret;

    /**
     * SampleService constructor.
     */
    public function __construct()
    {
        $this->baseUri = config('services.samplepaywall.base_uri') ?? '';
        $this->key = config('services.samplepaywall.key') ?? '';
        $this->secret = config('services.samplepaywall.secret') ?? '';
    }

    /**
     * Resolves the authorization for Sample Paywall API requests.
     *
     * @param array $queryParams The query parameters for the request.
     * @param array $formParams The form parameters for the request.
     * @param array $headers The headers for the request.
     * @return void
     */
    protected function resolveAuthorization(&$queryParams, &$formParams, &$headers): void
    {
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    /**
     * Decodes the response from the Sample Paywall API.
     *
     * @param mixed $response The response from the Sample Paywall API.
     * @return mixed The decoded response.
     */
    protected function decodeResponse($response)
    {
        return json_decode($response);
    }

    /**
     * Resolves the access token for Sample Paywall API authentication.
     *
     * @return string The resolved access token.
     */
    private function resolveAccessToken(): string
    {
        return "Bearer {$this->secret}";
    }

    /**
     * Generates a payment link for the given request.
     *
     * @param Request $request The HTTP request containing the payment details.
     * @return string The generated payment link.
     */
    public function getPaymentLink(Request $request, string $returnUrl, string $cancelUrl): string
    {
        $request->validate([
            'payment_method' => 'required',
        ]);

        // Just for testing
        $some_random_id = $this->uuid_create();

        return "https://example.com/payment/" . $some_random_id;
    }

    /**
     * It will create random uuid
     *
     * @return string
     */
    public function uuid_create(){
        return uuid_create();
    }

    /**
     * Handles the approval process for the payment.
     *
     * @return array The result of the approval process.
     */
    public function handleApproval(): array
    {
        return [
            'status' => 'success',
        ];
    }

}
