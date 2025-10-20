<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Plan;
use App\Models\User;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.sanitise');
        Config::$is3ds = config('midtrans.3ds');
    }

    public function createSnapTransaction(Transaction $transaction, Plan $plan, User $user): array
    {
        $notificationUrl = config('midtrans.webhook_url');

        $params = [
            'transaction_details' => [
                'order_id' => $transaction->transaction_code,
                'gross_amount' => (int) $transaction->amount,
            ],
            'item_details' => [
                [
                    'id' => 'PLAN-' . $plan->id,
                    'price' => (int) $plan->price,
                    'quantity' => 1,
                    'name' => $plan->name,
                ]
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'callbacks' => [
                'finish' => config('midtrans.finish_redirect_url'),
                'notification' => $notificationUrl,
                'unfinish' => config('midtrans.finish_redirect_url') . '?status=unfinish',
                'error' => config('midtrans.finish_redirect_url') . '?status=error',
            ]
        ];

        try {
            $snapResponse = Snap::createTransaction($params);

            return [
                'payment_gateway_id' => $snapResponse->token,
                'redirect_url' => $snapResponse->redirect_url,
                'info' => [
                    'message' => 'Please continue the payment with Midtrans link.',
                    'midtrans_mode' => Config::$isProduction ? 'Production' : 'Sandbox'
                ]
            ];

        } catch (Exception $e) {
            \Log::error("MIDTRANS_SNAP_CREATION_FAILED for TRX: {$transaction->transaction_code}", [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw new Exception("Failed to connect Midtrans API: " . $e->getMessage());
        }
    }
}
