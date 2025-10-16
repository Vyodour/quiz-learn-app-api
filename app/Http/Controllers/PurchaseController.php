<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Transaction;
use App\Http\Requests\StorePurchaseRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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

        try {
            $transactionData = DB::transaction(function () use ($user, $plan, $request) {
                $transactionCode = 'TRX-' . time() . '-' . Str::random(10);
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'transaction_code' => $transactionCode,
                    'amount' => $plan->price,
                    'currency' => 'IDR',
                    'status' => 'pending',
                    'payment_gateway' => 'Midtrans Snap',
                    'plan_id' => $plan->id,
                ]);

                $midtransResult = $this->midtransService->createSnapTransaction($transaction, $plan, $user);

                $transaction->payment_gateway_id = $midtransResult['payment_gateway_id'] ?? null;
                $transaction->gateway_response = $midtransResult;
                $transaction->save();

                return [
                    'transaction_code' => $transaction->transaction_code,
                    'payment_url' => $midtransResult['redirect_url'] ?? null,
                    'payment_info' => $midtransResult['info'] ?? null,
                ];
            });

            return ResponseHelper::success(
                'Purchase initiated. Start the transaction.',
                $transactionData,
                'purchase_data',
                201
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to purchase. Error: ' . $e->getMessage(), 500);
        }
    }
}
