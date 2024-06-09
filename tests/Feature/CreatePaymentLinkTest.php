<?php

use App\Models\User;
use App\Models\Transaction;
use App\Services\PayPalService;
use App\Resolvers\PaymentPlatformResolver;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->user = User::factory()->create();
    Passport::actingAs($this->user, ['create-transaction']);
});

afterEach(function () {
    Mockery::close();
});

it('creates a payment link', function () {
    $data = [
        'amount' => 100.00,
        'currency' => 'USD',
        'provider' => 'paypal',
        'payment_platform' => 'paypal',
        'return_url' => 'http://example.com/return',
        'cancel_url' => 'http://example.com/cancel',
    ];

    $payPalServiceMock = Mockery::mock(PayPalService::class);
    $payPalServiceMock->shouldReceive('getPaymentLink')
        ->andReturn('http://payment.link');

    $this->instance(PayPalService::class, $payPalServiceMock);

    $resolverMock = Mockery::mock(PaymentPlatformResolver::class);
    $resolverMock->shouldReceive('resolveService')
        ->andReturn($payPalServiceMock);

    $this->instance(PaymentPlatformResolver::class, $resolverMock);

    $response = $this->postJson('/api/create-payment-link', $data);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'status',
        'message',
        'transaction' => [
            'id',
            'amount',
            'currency',
            'provider',
            'user_id',
            'status',
            'payment_link',
        ],
    ]);

    $transaction = Transaction::first();
    expect($transaction->payment_link)->toBe('http://payment.link');
});

it('returns validation errors when creating payment link', function () {
    $response = $this->postJson('/api/create-payment-link', []);

    $response->assertStatus(422);
    $response->assertJsonStructure(['errors']);
});

it('returns unauthorized when missing scope', function () {
    Passport::actingAs($this->user, []);

    $response = $this->postJson('/api/create-payment-link', [
        'amount' => 100.00,
        'currency' => 'USD',
        'provider' => 'paypal',
        'payment_platform' => 'paypal',
        'return_url' => 'http://example.com/return',
        'cancel_url' => 'http://example.com/cancel',
    ]);

    $response->assertStatus(403);
});
