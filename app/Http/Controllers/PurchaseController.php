<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Transaction;
use App\Http\Requests\StorePurchaseRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\MidtransService;
use Illuminate\Support\Str;
use Exception;

class PurchaseController extends Controller
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->middleware('auth:sanctum');
        $this->midtransService = $midtransService;
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $user = $request->user();
        $plan = Plan::find($request->plan_id);

        if (!$plan || !$plan->is_active) {
            return ResponseHelper::error('Package not found!.', 404);
        }

        if ($user->hasActiveSubscription()) {
            return ResponseHelper::error(
                'Access Denied: You already have an active subscription.',
                409,
                ['current_subscription' => $user->getActiveSubscription()]
            );
        }

        $selectedGateway = $request->payment_gateway;

        try {
            $transactionData = DB::transaction(function () use ($user, $plan, $request, $selectedGateway) {
                $transactionCode = 'TRX-' . time() . '-' . Str::random(10);

                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'transaction_code' => $transactionCode,
                    'amount' => $plan->price,
                    'currency' => 'IDR',
                    'status' => 'pending',
                    'payment_gateway' => $selectedGateway,
                    'plan_id' => $plan->id,
                ]);

                $gatewayResult = [];

                if ($selectedGateway === 'midtrans') {
                    $gatewayResult = $this->midtransService->createSnapTransaction($transaction, $plan, $user);
                }
                else {
                    throw new Exception("Payment gateway {$selectedGateway} is currently not available for transaction creation.");
                }


                $transaction->payment_gateway_id = $gatewayResult['payment_gateway_id'] ?? null;
                $transaction->gateway_response = $gatewayResult;
                $transaction->save();

                return [
                    'transaction_code' => $transaction->transaction_code,
                    'payment_url' => $gatewayResult['redirect_url'] ?? null,
                    'payment_info' => $gatewayResult['info'] ?? null,
                ];
            });

            return ResponseHelper::success(
                'Purchase initiated. Start the transaction.',
                $transactionData,
                'purchase_data',
                201
            );

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("PurchaseController Store Error: " . $e->getMessage(), ['exception' => $e]);
            return ResponseHelper::error('Failed to purchase. Error: ' . $e->getMessage(), 500);
        }
    }
}
