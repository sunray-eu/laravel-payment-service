<?php

use App\Events\TransactionStatusUpdated;
use App\Listeners\LogTransactionStatusUpdated;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::spy();
});

it('logs transaction status updated event', function () {
    $transaction = Mockery::mock(Transaction::class);
    $event = new TransactionStatusUpdated($transaction, 'new', 'completed');

    (new LogTransactionStatusUpdated())->handle($event);

    Log::shouldHaveReceived('info')->once()->with('Transaction status updated', [
        'transaction' => $transaction,
        'old_status' => 'new',
        'new_status' => 'completed'
    ]);
});
