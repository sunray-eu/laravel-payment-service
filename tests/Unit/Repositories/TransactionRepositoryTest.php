<?php

use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Events\TransactionCreated;
use App\Events\TransactionStatusUpdated;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
});

it('creates a transaction and dispatches event', function () {
    $repository = new TransactionRepository();
    $data = Transaction::factory()->make()->toArray();

    $transaction = $repository->create($data);

    Event::assertDispatched(TransactionCreated::class, function ($event) use ($transaction) {
        return $event->transaction->id === $transaction->id;
    });

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->amount)->toBe($data['amount']);
});

it('updates transaction status and dispatches event', function () {
    $repository = new TransactionRepository();

    $newStatus = 'processing';
    $oldStatus = 'new';

    $transaction = Transaction::factory()->create(['status' => $oldStatus]);

    $updatedTransaction = $repository->updateStatus($transaction, $newStatus);


    Event::assertDispatched(TransactionStatusUpdated::class, function ($event) use ($updatedTransaction, $oldStatus, $newStatus) {
        return $event->transaction->id === $updatedTransaction->id
            && $event->oldStatus === $oldStatus
            && $event->newStatus === $newStatus;
    });

    expect($updatedTransaction->status)->toBe($newStatus);
});
