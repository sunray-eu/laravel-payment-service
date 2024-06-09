<?php

use App\Events\TransactionCreated;
use App\Listeners\LogTransactionCreated;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::spy();
});

it('logs transaction created event', function () {
    $transaction = Mockery::mock(Transaction::class);
    $event = new TransactionCreated($transaction);

    (new LogTransactionCreated())->handle($event);

    Log::shouldHaveReceived('info')->once()->with('Transaction created', ['transaction' => $transaction]);
});
