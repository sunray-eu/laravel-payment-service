<?php

namespace App\Http\Controllers;

use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Http\Request;
use App\Repositories\TransactionRepository;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class TransactionController
 * @package App\Http\Controllers
 *
 * Handles transaction status updates.
 */
class TransactionController extends Controller
{
    protected TransactionRepository $transactionRepository;
    protected PaymentPlatformResolver $paymentPlatformResolver;

    /**
     * TransactionController constructor.
     *
     * @param TransactionRepository $transactionRepository
     * @param PaymentPlatformResolver $paymentPlatformResolver
     */
    public function __construct(TransactionRepository $transactionRepository, PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->transactionRepository = $transactionRepository;
        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }

    /**
     * Updates the status of a transaction.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'status' => 'required|string|in:new,processing,completed,failed',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for transaction status update', ['errors' => $validator->errors()]);
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            $transaction = Transaction::findOrFail($data['transaction_id']);
            $validTransitions = [
                'new' => ['processing'],
                'processing' => ['completed', 'failed'],
            ];

            if (!isset($validTransitions[$transaction->status]) || !in_array($data['status'], $validTransitions[$transaction->status])) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'Invalid transition',
                    'message' => 'Cannot change status from ' . $transaction->status . ' to ' . $data['status']
                ], 422);
            }

            $response = [];

            if ($data['status'] === 'completed') {
                $paymentService = $this->paymentPlatformResolver->resolveService($transaction->provider);
                $approvalResult = $paymentService->handleApproval();
                if (empty($approvalResult['status']) || $approvalResult['status'] === 'failed') {
                    $response = [
                        'status' => 'error',
                        'error' => 'Approval failed',
                        'message' => $approvalResult['message'] ?? null,
                    ];
                    $this->transactionRepository->updateStatus($transaction, 'failed');
                } elseif ($approvalResult['status'] === 'requires_action') {
                    $response = [
                        'status' => 'requires_action',
                        'message' => 'You need to complete one more action',
                        'action' => $approvalResult['action'],
                    ];
                } else {
                    $this->transactionRepository->updateStatus($transaction, 'completed');
                    $response = [
                        'status' => 'success',
                        'message' => 'Transaction completed successfully',
                        'transaction' => $transaction->toJson(),
                    ];
                }
            } else {
                $this->transactionRepository->updateStatus($transaction, $data['status']);
                $response = [
                    'status' => 'success',
                    'message' => 'Transaction status updated successfully',
                    'transaction' => $transaction->toJson(),
                ];
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Failed to update transaction status', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update transaction status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
