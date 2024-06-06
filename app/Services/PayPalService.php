<?php

namespace App\Services;

use Illuminate\Http\Request;
use PaywallService;

/**
 * Class PayPalService
 * @package App\Services
 *
 * Handles interactions with PayPal's API for payment processing.
 */
class PayPalService extends PaywallService
{
    /**
     * The base URI for the PayPal API.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * The client ID for PayPal API authentication.
     *
     * @var string
     */
    protected $clientId;

    /**
     * The client secret for PayPal API authentication.
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * PayPal plans configuration.
     *
     * @var array
     */
    protected $plans;

    /**
     * PayPalService constructor.
     */
    public function __construct()
    {
        $this->baseUri = config('services.paypal.base_uri');
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->plans = config('services.paypal.plans');
    }

    /**
     * Resolves the authorization for PayPal API requests.
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
     * Decodes the response from the PayPal API.
     *
     * @param mixed $response The response from the PayPal API.
     * @return mixed The decoded response.
     */
    protected function decodeResponse($response)
    {
        return json_decode($response);
    }

    /**
     * Resolves the access token for PayPal API authentication.
     *
     * @return string The resolved access token.
     */
    protected function resolveAccessToken(): string
    {
        $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");
        return "Basic {$credentials}";
    }

    /**
     * Generates a payment link for the given request.
     *
     * @param Request $request The HTTP request containing the payment details.
     * @return string The generated payment link.
     */
    public function getPaymentLink(Request $request): string
    {
        $order = $this->createOrder($request->value, $request->currency);

        $orderLinks = collect($order->links);
        $approve = $orderLinks->where('rel', 'approve')->first();

        session()->put('approvalId', $order->id);

        return $approve->href;
    }

    /**
     * Handles the approval process for the payment.
     *
     * @return array The result of the approval process.
     */
    public function handleApproval(): array
    {
        if (session()->has('approvalId')) {
            $approvalId = session()->get('approvalId');

            $payment = $this->capturePayment($approvalId);

            if (empty($payment) || !empty($payment->error)) {
                return [
                    'status' => 'failed',
                    'message' => $payment['error'] ?? 'We cannot capture the payment. Try again, please'
                ];
            }

            $name = $payment->payer->name->given_name;
            $payment = $payment->purchase_units[0]->payments->captures[0]->amount;
            $amount = $payment->value;
            $currency = $payment->currency_code;

            return [
                'status' => 'success',
                'name' => $name,
                'amount' => $amount,
                'currency' => $currency,
            ];
        }

        return [
            'status' => 'failed',
            'message' => 'We cannot capture the payment. Try again, please'
        ];
    }

    /**
     * Creates an order with the specified value and currency.
     *
     * @param float $value The value of the order.
     * @param string $currency The currency of the order.
     * @return mixed The created order.
     */
    public function createOrder($value, $currency)
    {
        return $this->makeRequest(
            'POST',
            '/v2/checkout/orders',
            [],
            [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => round($value * $factor = $this->resolveFactor($currency)) / $factor,
                        ]
                    ]
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('approval'),
                    'cancel_url' => route('cancelled'),
                ]
            ],
            [],
            true
        );
    }

    /**
     * Captures the payment for the specified approval ID.
     *
     * @param string $approvalId The approval ID.
     * @return mixed The captured payment.
     */
    private function capturePayment($approvalId)
    {
        return $this->makeRequest(
            'POST',
            "/v2/checkout/orders/{$approvalId}/capture",
            [],
            [],
            [
                'Content-Type' => 'application/json'
            ]
        );
    }

    /**
     * Resolves the factor for the specified currency.
     *
     * @param string $currency The currency to resolve the factor for.


     * @return int The resolved factor.
     */
    private function resolveFactor($currency): int
    {
        $zeroDecimalCurrencies = ['JPY'];

        return in_array(strtoupper($currency), $zeroDecimalCurrencies) ? 1 : 100;
    }
}
