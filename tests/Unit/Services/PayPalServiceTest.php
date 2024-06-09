<?php

use App\Services\PayPalService;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

beforeEach(function () {
    Config::set('services.paypal.base_uri', 'https://api.sandbox.paypal.com');
    Config::set('services.paypal.client_id', 'test_client_id');
    Config::set('services.paypal.client_secret', 'test_client_secret');
});

afterEach(function () {
    Mockery::close();
});

it('generates correct access token', function () {
    $service = new PayPalService();

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('resolveAccessToken');
    $method->setAccessible(true);

    $token = $method->invoke($service);

    $expectedTokenAuth = 'Basic ' . base64_encode('test_client_id:test_client_secret');
    expect($token)->toBe($expectedTokenAuth);
});

it('decodes response correctly', function () {
    $service = new PayPalService();
    $response = json_encode(['key' => 'value']);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('decodeResponse');
    $method->setAccessible(true);

    $decoded = $method->invoke($service, $response);

    expect($decoded)->toEqual((object) ['key' => 'value']);
});

it('resolves factor correctly', function () {
    $service = new PayPalService();

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('resolveFactor');
    $method->setAccessible(true);

    $resultWithJpy = $method->invoke($service, 'JPY');
    $resultWithOther = $method->invoke($service, 'EUR');

    expect($resultWithJpy)->toEqual(1);
    expect($resultWithOther)->toEqual(100);
});

it('creates an order with correct parameters', function () {
    $service = Mockery::mock(PayPalService::class)->makePartial();
    $service->shouldReceive('makeRequest')->once()->with(
        'POST',
        '/v2/checkout/orders',
        [],
        Mockery::on(function ($params) {
            return $params['intent'] === 'CAPTURE'
                && $params['purchase_units'][0]['amount']['currency_code'] === 'USD'
                && $params['purchase_units'][0]['amount']['value'] === 100.0;
        }),
        [],
        true
    )->andReturn((object) ['links' => [(object) ['rel' => 'approve', 'href' => 'http://approval-link.com']]]);

    $order = $service->createOrder(100.0, 'USD', 'http://return-url.com', 'http://cancel-url.com');

    expect($order->links[0]->href)->toBe('http://approval-link.com');
});

it('captures payment correctly', function () {
    $service = Mockery::mock(PayPalService::class)->makePartial();

    $service->shouldReceive('makeRequest')->once()->with(
        'POST',
        '/v2/checkout/orders/sample-id/capture',
        [],
        [],
        ['Content-Type' => 'application/json']
    )->andReturn((object) ['status' => 'COMPLETED']);

    $response = $service->capturePayment('sample-id');

    expect($response->status)->toBe('COMPLETED');
});

it('gets payment link correctly', function () {
    $service = Mockery::mock(PayPalService::class)->makePartial();

    $request = new Request([
        'value' => 100.0,
        'currency' => 'USD',
    ]);

    $orderMock = (object) [
        'id' => 'ORDER_ID',
        'links' => [
            (object) [
                'rel' => 'approve',
                'href' => 'http://approval-link.com'
            ]
        ]
    ];

    $service->shouldReceive('createOrder')->once()->with(
        100.0,
        'USD',
        'http://return-url.com',
        'http://cancel-url.com'
    )->andReturn($orderMock);

    $link = $service->getPaymentLink($request, 'http://return-url.com', 'http://cancel-url.com');

    expect($link)->toBe('http://approval-link.com');
    expect(session()->get('approvalId'))->toBe('ORDER_ID');
});

it('handles approval successfully', function () {
    $service = Mockery::mock(PayPalService::class)->makePartial();

    session()->put('approvalId', 'ORDER_ID');

    $paymentMock = (object) [
        'payer' => (object) ['name' => (object) ['given_name' => 'John']],
        'purchase_units' => [
            (object) [
                'payments' => (object) [
                    'captures' => [
                        (object) [
                            'amount' => (object) [
                                'value' => '100.00',
                                'currency_code' => 'USD'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $service->shouldReceive('capturePayment')->once()->with('ORDER_ID')->andReturn($paymentMock);

    $result = $service->handleApproval();

    expect($result)->toEqual([
        'status' => 'success',
        'name' => 'John',
        'amount' => '100.00',
        'currency' => 'USD'
    ]);
});

it('handles approval failure', function () {
    $service = Mockery::mock(PayPalService::class)->makePartial();

    session()->put('approvalId', 'ORDER_ID');

    $paymentMock = (object) [
        'error' => 'Some error'
    ];

    $service->shouldReceive('capturePayment')->once()->with('ORDER_ID')->andReturn($paymentMock);

    $result = $service->handleApproval();

    expect($result)->toEqual([
        'status' => 'failed',
        'message' => 'Some error'
    ]);
});

it('handles approval when session is missing', function () {
    $service = new PayPalService();

    $result = $service->handleApproval();

    expect($result)->toEqual([
        'status' => 'failed',
        'message' => 'We cannot capture the payment. Try again, please'
    ]);
});
