<?php

use App\Models\Transaction;
use App\Resolvers\PaymentPlatformResolver;
use App\Repositories\TransactionRepository;
use App\Http\Controllers\TransactionController;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->transactionRepository = new TransactionRepository();
    $this->paymentPlatformResolver = Mockery::mock(PaymentPlatformResolver::class);
    $this->controller = new TransactionController($this->transactionRepository, $this->paymentPlatformResolver);
});

it('updates transaction status successfully', function () {
    $transaction = Transaction::factory()->create(['status' => 'new']);
    $request = Request::create('/api/update-transaction-status', 'POST', [
        'transaction_id' => $transaction->id,
        'status' => 'processing',
    ]);

    $response = $this->controller->updateStatus($request);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData()->status)->toBe('success');
    expect(json_decode($response->getData()->transaction)->status)->toBe('processing');
});

it('fail on approval from service fail', function () {
    $transaction = Transaction::factory()->create([
        'status' => 'processing',
        'provider' => 'paypal'
    ]);
    $request = Request::create('/api/update-transaction-status', 'POST', [
        'transaction_id' => $transaction->id,
        'status' => 'completed',
    ]);
    $service = Mockery::mock(PayPalService::class);
    $service->shouldReceive('handleApproval')->andReturn([
        'status' => 'failed'
    ]);

    $this->paymentPlatformResolver
        ->shouldReceive('resolveService')
        ->andReturn($service);

    $response = $this->controller->updateStatus($request);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData()->status)->toBe('error');
    expect($transaction->refresh()->status)->toBe('failed');
});

it('should return approval action', function () {
    $transaction = Transaction::factory()->create([
        'status' => 'processing',
        'provider' => 'paypal'
    ]);
    $request = Request::create('/api/update-transaction-status', 'POST', [
        'transaction_id' => $transaction->id,
        'status' => 'completed',
    ]);
    $service = Mockery::mock(PayPalService::class);
    $service->shouldReceive('handleApproval')->andReturn([
        'status' => 'requires_action',
        'action' => 'https://example.com/approval'
    ]);

    $this->paymentPlatformResolver
        ->shouldReceive('resolveService')
        ->andReturn($service);

    $response = $this->controller->updateStatus($request);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true))->toBe([
        'status' => 'requires_action',
        'message' => 'You need to complete one more action',
        'action' => 'https://example.com/approval'
    ]);
    expect($transaction->refresh()->status)->toBe('processing');
});

it('fails to update transaction status with validation errors', function () {
    Log::spy();
    $request = Request::create('/api/update-transaction-status', 'POST', [
        'status' => 'processing',
    ]);

    $response = $this->controller->updateStatus($request);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData()->errors)->toBeObject();
    expect(isset($response->getData()->errors->transaction_id))->toBeTrue();
    Log::shouldHaveReceived('error')->once();
});

it('handles exceptions when updating transaction status', function () {
    $transaction = Transaction::factory()->create(['status' => 'processing']);
    $request = Request::create('/api/update-transaction-status', 'POST', [
        'transaction_id' => $transaction->id,
        'status' => 'completed',
    ]);

    Log::spy();
    $this->paymentPlatformResolver
        ->shouldReceive('resolveService')
        ->andThrow(new Exception('Service error'));

    $response = $this->controller->updateStatus($request);

    expect($response->getStatusCode())->toBe(500);
    expect($response->getData()->status)->toBe('error');
    expect($response->getData()->message)->toBe('Failed to update transaction status');
    Log::shouldHaveReceived('error')->once();
});

it('prevents invalid status transitions', function () {
    $transaction = Transaction::factory()->create(['status' => 'new']);
    $request = Request::create('/api/update-transaction-status', 'POST', [
        'transaction_id' => $transaction->id,
        'status' => 'completed',
    ]);

    $response = $this->controller->updateStatus($request);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData()->status)->toBe('error');
    expect($response->getData()->message)->toContain('Cannot change status from new to completed');
});
