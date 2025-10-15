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
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createSnapTransaction(Transaction $transaction, Plan $plan, User $user): array
    {
        $notificationUrl = config('midtrans.webhook_url');

        $params = [
            'transaction_details' => [
                'order_id' => $transaction->transaction_code,
                'gross_amount' => $transaction->amount,
            ],
            'item_details' => [
                [
                    'id' => 'PLAN-' . $plan->id,
                    'price' => $plan->price,
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
                    'message' => 'Silakan lanjutkan pembayaran melalui link redirect Midtrans.',
                    'midtrans_mode' => Config::$isProduction ? 'Production' : 'Sandbox'
                ]
            ];

        } catch (Exception $e) {
            throw new Exception("Gagal menghubungi Midtrans API: " . $e->getMessage());
        }
    }
}
