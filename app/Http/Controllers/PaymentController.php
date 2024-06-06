<?php

namespace App\Http\Controllers;

use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Http\Request;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * Class PaymentController
 * @package App\Http\Controllers
 *
 * Handles the creation of payment links using various payment platforms.
 */
class PaymentController extends Controller
{
    protected TransactionRepository $transactionRepository;
    protected PaymentPlatformResolver $paymentPlatformResolver;

    /**
     * PaymentController constructor.
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
     * Creates a payment link.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPaymentLink(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'provider' => 'required|string',
            'payment_platform' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for payment link creation', ['errors' => $validator->errors()]);
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            // Resolve the payment service and create the payment link
            $paymentService = $this->paymentPlatformResolver->resolveService($data['payment_platform']);
            $paymentLink = $paymentService->getPaymentLink($request);

            // Create a new transaction record
            $transaction = $this->transactionRepository->create([
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'provider' => $data['provider'],
                'user_id' => Auth::id(),
                'status' => 'new',
                'payment_link' => $paymentLink
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment link created successfully',
                'transaction' => $transaction
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create payment link', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to create payment link',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
