<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use Illuminate\Support\Facades\Log;

class LogTransactionCreated
{
    public function handle(TransactionCreated $event)
    {
        Log::info('Transaction created', ['transaction' => $event->transaction]);
    }
}
