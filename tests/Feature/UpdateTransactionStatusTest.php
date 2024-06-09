<?php

use App\Models\User;
use App\Models\Transaction;
use App\Services\PayPalService;
use App\Resolvers\PaymentPlatformResolver;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->transaction = Transaction::factory()->create(['user_id' => $this->user->id, 'status' => 'new']);
    Passport::actingAs($this->user, ['update-transaction']);
});

afterEach(function () {
    Mockery::close();
});

it('updates transaction status', function () {
    $data = [
        'transaction_id' => $this->transaction->id,
        'status' => 'processing',
    ];

    $response = $this->postJson('/api/update-transaction-status', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Transaction status updated successfully',
        'transaction' => Transaction::find($this->transaction->id)->toJson(),
    ]);
});

it('returns validation errors when updating transaction status', function () {
    $response = $this->postJson('/api/update-transaction-status', []);

    $response->assertStatus(422);
    $response->assertJsonStructure(['errors']);
});

it('prevents invalid status transitions', function () {
    $transaction = Transaction::factory()->create(['user_id' => $this->user->id, 'status' => 'completed']);
    $data = [
        'transaction_id' => $transaction->id,
        'status' => 'processing',
    ];

    $response = $this->postJson('/api/update-transaction-status', $data);

    $response->assertStatus(422);
    $response->assertJson([
        'status' => 'error',
        'error' => 'Invalid transition',
    ]);
});

it('captures payment approval successfully', function () {
    $transaction = Transaction::factory()->create(['status' => 'processing']);
    $data = [
        'transaction_id' => $transaction->id,
        'status' => 'completed',
    ];

    $payPalServiceMock = Mockery::mock(PayPalService::class);
    $payPalServiceMock->shouldReceive('handleApproval')
        ->andReturn(['status' => 'success']);

    $this->instance(PayPalService::class, $payPalServiceMock);

    $resolverMock = Mockery::mock(PaymentPlatformResolver::class);
    $resolverMock->shouldReceive('resolveService')
        ->andReturn($payPalServiceMock);

    $this->instance(PaymentPlatformResolver::class, $resolverMock);

    session()->put('approvalId', 'dummy_approval_id');

    $response = $this->postJson('/api/update-transaction-status', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Transaction completed successfully',
    ]);

    expect($transaction->fresh()->status)->toBe('completed');
});

it('returns unauthorized when missing scope', function () {
    Passport::actingAs($this->user, []);

    $response = $this->postJson('/api/update-transaction-status', [
        'transaction_id' => $this->transaction->id,
        'status' => 'processing',
    ]);

    $response->assertStatus(403);
});
