<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Traits\ConsumesExternalServices;
use PaywallService;

/**
 * Class StripeService
 * @package App\Services
 *
 * Handles interactions with Stripe's API for payment processing.
 */
class StripeService extends PaywallService
{
    use ConsumesExternalServices;

    /**
     * The base URI for the Stripe API.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * The key for Stripe API authentication.
     *
     * @var string
     */
    protected $key;

    /**
     * The secret for Stripe API authentication.
     *
     * @var string
     */
    protected $secret;

    /**
     * Stripe plans configuration.
     *
     * @var array
     */
    protected $plans;

    /**
     * StripeService constructor.
     */
    public function __construct()
    {
        $this->baseUri = config('services.stripe.base_uri');
        $this->key = config('services.stripe.key');
        $this->secret = config('services.stripe.secret');
        $this->plans = config('services.stripe.plans');
    }

    /**
     * Resolves the authorization for Stripe API requests.
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
     * Decodes the response from the Stripe API.
     *
     * @param mixed $response The response from the Stripe API.
     * @return mixed The decoded response.
     */
    protected function decodeResponse($response)
    {
        return json_decode($response);
    }

    /**
     * Resolves the access token for Stripe API authentication.
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
    public function getPaymentLink(Request $request): string
    {
        $request->validate([
            'payment_method' => 'required',
        ]);

        $intent = $this->createIntent($request->value, $request->currency, $request->payment_method);

        session()->put('paymentIntentId', $intent->id);

        return route('approval');
    }

    /**
     * Handles the approval process for the payment.
     *
     * @return array The result of the approval process.
     */
    public function handleApproval(): array
    {
        if (session()->has('paymentIntentId')) {
            $paymentIntentId = session()->get('paymentIntentId');

            $confirmation = $this->confirmPayment($paymentIntentId);

            if ($confirmation->status === 'requires_action') {
                $clientSecret = $confirmation->client_secret;

                return [
                    'status' => 'requires_action',
                    'action' => view('stripe.3d-secure')->with([
                        'clientSecret' => $clientSecret,
                    ])
                ];
            }

            if ($confirmation->status === 'succeeded') {
                $name = $confirmation->charges->data[0]->billing_details->name;
                $currency = strtoupper($confirmation->currency);
                $amount = $confirmation->amount / $this->resolveFactor($currency);

                return [
                    'status' => 'success',
                    'name' => $name,
                    'amount' => $amount,
                    'currency' => $currency,
                ];
            }
        }

        return [
            'status' => 'failed',
            'message' => 'We cannot capture the payment. Try again, please'
        ];
    }

    /**
     * Creates a payment intent with the specified value, currency, and payment method.
     *
     * @param float $value The value of the payment.
     * @param string $currency The currency of the payment.
     * @param string $paymentMethod The payment method.
     * @return mixed The created payment intent.
     */
    private function createIntent($value, $currency, $paymentMethod)
    {
        return $this->makeRequest(
            'POST',
            '/v1/payment_intents',
            [],
            [
                'amount' => round($value * $this->resolveFactor($currency)),
                'currency' => strtolower($currency),
                'payment_method' => $paymentMethod,
                'confirmation_method' => 'manual',
            ],
        );
    }

    /**
     * Confirms the payment for the specified payment intent ID.
     *
     * @param string $paymentIntentId The payment intent ID.
     * @return mixed The confirmed payment.
     */
    private function confirmPayment($paymentIntentId)
    {
        return $this->makeRequest(
            'POST',
            "/v1/payment_intents/{$paymentIntentId}/confirm",
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
