<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Transaction;
use App\Http\Requests\StorePurchaseRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $user = $request->user();
        $plan = Plan::find($request->plan_id);

        if (!$plan) {
            return ResponseHelper::error('Paket tidak ditemukan.', 404);
        }

        try {
            $transactionData = DB::transaction(function () use ($user, $plan, $request) {
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'transaction_code' => 'TXN-' . time() . '-' . uniqid(),
                    'amount' => $plan->price,
                    'currency' => 'IDR',
                    'status' => 'pending',
                    'payment_gateway' => $request->payment_gateway,
                ]);

                $paymentGatewayResponse = $this->callPaymentGateway($transaction, $plan, $user);

                $transaction->gateway_response = $paymentGatewayResponse;
                $transaction->save();

                return [
                    'transaction' => $transaction,
                    'payment_url' => $paymentGatewayResponse['redirect_url'] ?? null,
                    'payment_info' => $paymentGatewayResponse['info'] ?? null,
                ];
            });

            return ResponseHelper::success(
                'Pembelian berhasil diinisiasi. Lanjutkan ke pembayaran.',
                $transactionData,
                'purchase_data',
                201
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Gagal menginisiasi pembelian. Error: ' . $e->getMessage(), 500);
        }
    }

    private function callPaymentGateway(Transaction $transaction, Plan $plan, $user): array
    {
        return [
            'redirect_url' => 'https://sandbox.paymentgateway.com/pay/' . $transaction->transaction_code,
            'gateway_id' => 'GATEWAY-' . rand(1000, 9999),
            'info' => [
                'va_number' => '8888000' . rand(100, 999),
                'expiry_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
                'instruction' => 'Please transfer to the VA number before expired',
            ],
        ];
    }
}
