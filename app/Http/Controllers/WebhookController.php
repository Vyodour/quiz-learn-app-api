<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Midtrans\Config;
use Midtrans\Notification;

class WebhookController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;

        Config::$isProduction = config('midtrans.is_production');
        Config::$serverKey = config('midtrans.server_key');
    }

    /**
     * Menerima notifikasi (Webhook) dari Midtrans.
     * Rute ini TIDAK menggunakan middleware 'auth'.
     *
     * @param Request $request Data notifikasi dari Midtrans.
     * @return \Illuminate\Http\JsonResponse
     */
    public function midtransHandler(Request $request)
    {
        try {
            $notification = new Notification();
        } catch (\Exception $e) {
            Log::error("MIDTRANS_WEBHOOK_VERIFICATION_FAILED: " . $e->getMessage());
            return response()->json(['message' => 'Invalid notification signature or error in Midtrans processing.'], 403);
        }

        $transactionStatus = $notification->transaction_status;
        $orderId = $notification->order_id;
        $fraudStatus = $notification->fraud_status;

        $transaction = Transaction::where('transaction_code', $orderId)->first();

        if (!$transaction) {
            Log::warning("WEBHOOK_TRANSACTION_NOT_FOUND: Order ID {$orderId}");
            return response()->json(['message' => 'Transaction not found!'], 404);
        }

        if (in_array($transaction->status, ['settlement', 'success', 'expire', 'cancel'])) {
            return response()->json(['message' => 'Transaction already finalized.'], 200);
        }

        DB::transaction(function () use ($transaction, $transactionStatus, $fraudStatus, $notification) {

            $updateData = [
                'gateway_response' => json_decode(json_encode($notification->getResponse()), true),
            ];

            $newStatus = $transaction->status;

            if ($transactionStatus == 'capture') {
                $newStatus = ($fraudStatus == 'accept') ? 'settlement' : 'denied';
            } elseif ($transactionStatus == 'settlement') {
                $newStatus = 'settlement';
            } elseif ($transactionStatus == 'pending') {
                $newStatus = 'pending';
            } elseif (in_array($transactionStatus, ['deny', 'cancel'])) {
                $newStatus = 'cancel';
            } elseif ($transactionStatus == 'expire') {
                $newStatus = 'expire';
            }

            $transaction->update(array_merge($updateData, ['status' => $newStatus]));

            if ($newStatus === 'settlement') {
                $transaction->paid_at = Carbon::now();
                $transaction->save();

                $this->subscriptionService->activateSubscription($transaction);
                Log::info("WEBHOOK_ACTIVATED_SUBSCRIPTION: User {$transaction->user_id} - TRX: {$transaction->transaction_code}");
            }
        });

        return response()->json(['message' => 'Notification processed successfully.'], 200);
    }
}
