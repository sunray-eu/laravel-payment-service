<?php

use App\Http\Controllers\PaymentController;
use App\Repositories\TransactionRepository;
use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->transactionRepository = Mockery::mock(TransactionRepository::class);
    $this->paymentPlatformResolver = Mockery::mock(PaymentPlatformResolver::class);
    $this->paymentController = new PaymentController($this->transactionRepository, $this->paymentPlatformResolver);

    Auth::shouldReceive('id')->andReturn(1);
});

it('creates a payment link successfully', function () {
    $request = Request::create('/create-payment-link', 'POST', [
        'amount' => 100,
        'currency' => 'USD',
        'provider' => 'paypal',
        'payment_platform' => 'paypal',
        'return_url' => 'https://return.url',
        'cancel_url' => 'https://cancel.url'
    ]);

    Validator::shouldReceive('make')->andReturn(Mockery::mock(\Illuminate\Validation\Validator::class, function ($mock) {
        $mock->shouldReceive('fails')->andReturn(false);
        $mock->shouldReceive('validated')->andReturn([
            'amount' => 100,
            'currency' => 'USD',
            'provider' => 'paypal',
            'payment_platform' => 'paypal',
            'return_url' => 'https://return.url',
            'cancel_url' => 'https://cancel.url'
        ]);
    }));

    $paypalService = Mockery::mock(\App\Services\PayPalService::class);
    $paypalService->shouldReceive('getPaymentLink')->andReturn('https://payment.link');
    $this->paymentPlatformResolver->shouldReceive('resolveService')->andReturn($paypalService);

    $this->transactionRepository->shouldReceive('create')->andReturn((object) [
        'id' => 1,
        'amount' => 100,
        'currency' => 'USD',
        'provider' => 'paypal',
        'user_id' => 1,
        'status' => 'new',
        'payment_link' => 'https://payment.link'
    ]);

    $response = $this->paymentController->createPaymentLink($request);
    $responseData = $response->getData();

    expect($responseData->status)->toBe('success');
    expect($responseData->message)->toBe('Payment link created successfully');
    expect($responseData->transaction->payment_link)->toBe('https://payment.link');
});

it('fails to create a payment link with invalid data', function () {
    $request = Request::create('/create-payment-link', 'POST', [
        'amount' => 100,
        'currency' => 'USD',
        'provider' => 'paypal'
    ]);

    Validator::shouldReceive('make')->andReturn(Mockery::mock(\Illuminate\Validation\Validator::class, function ($mock) {
        $mock->shouldReceive('fails')->andReturn(true);
        $mock->shouldReceive('errors')->andReturn(['payment_platform' => 'The payment platform field is required.']);
    }));

    $response = $this->paymentController->createPaymentLink($request);
    $responseData = $response->getData();

    expect($response->status())->toBe(422);
    expect($responseData->errors)->toHaveKey('payment_platform');
});

it('handles exceptions when creating a payment link', function () {
    $request = Request::create('/create-payment-link', 'POST', [
        'amount' => 100,
        'currency' => 'USD',
        'provider' => 'paypal',
        'payment_platform' => 'paypal',
        'return_url' => 'https://return.url',
        'cancel_url' => 'https://cancel.url'
    ]);

    Validator::shouldReceive('make')->andReturn(Mockery::mock(\Illuminate\Validation\Validator::class, function ($mock) {
        $mock->shouldReceive('fails')->andReturn(false);
        $mock->shouldReceive('validated')->andReturn([
            'amount' => 100,
            'currency' => 'USD',
            'provider' => 'paypal',
            'payment_platform' => 'paypal',
            'return_url' => 'https://return.url',
            'cancel_url' => 'https://cancel.url'
        ]);
    }));

    $paypalService = Mockery::mock(\App\Services\PayPalService::class);
    $paypalService->shouldReceive('getPaymentLink')->andThrow(new Exception('Failed to create payment link'));

    $this->paymentPlatformResolver->shouldReceive('resolveService')->andReturn($paypalService);

    $response = $this->paymentController->createPaymentLink($request);
    $responseData = $response->getData();

    expect($response->status())->toBe(500);
    expect($responseData->status)->toBe('failed');
    expect($responseData->message)->toBe('Failed to create payment link');
    expect($responseData->error)->toBe('Failed to create payment link');
});
