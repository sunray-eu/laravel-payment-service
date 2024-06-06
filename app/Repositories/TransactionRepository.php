<?php

namespace App\Repositories;

use App\Events\TransactionCreated;
use App\Events\TransactionStatusUpdated;
use App\Models\Transaction;

/**
 * Class TransactionRepository
 * @package App\Repositories
 *
 * Handles the creation and status updates of transactions.
 */
class TransactionRepository
{
    /**
     * Creates a new transaction.
     *
     * @param array $data The data to create the transaction with.
     * @return Transaction The created transaction.
     */
    public function create(array $data)
    {
        $transaction = Transaction::create($data);
        TransactionCreated::dispatch($transaction);
        return $transaction;
    }

    /**
     * Updates the status of a transaction.
     *
     * @param Transaction $transaction The transaction to update.
     * @param string $status The new status of the transaction.
     * @return Transaction The updated transaction.
     */
    public function updateStatus(Transaction $transaction, $status)
    {
        $oldStatus = $transaction->status;
        $transaction->status = $status;
        $transaction->save();

        TransactionStatusUpdated::dispatch($transaction, $oldStatus, $status);

        return $transaction;
    }
}
