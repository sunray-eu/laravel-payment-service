<?php

namespace App\Listeners;

use App\Events\TransactionStatusUpdated;
use Illuminate\Support\Facades\Log;

class LogTransactionStatusUpdated
{
    public function handle(TransactionStatusUpdated $event)
    {
        Log::info('Transaction status updated', [
            'transaction' => $event->transaction,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
        ]);
    }
}
