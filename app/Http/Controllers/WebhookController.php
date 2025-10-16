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

    public function midtransHandler(Request $request)
    {
        $orderId = $request->input('order_id');

        try {
            $notification = new Notification();
        } catch (\Exception $e) {
            Log::error("MIDTRANS_WEBHOOK_VERIFICATION_FAILED: Order ID {$orderId} - " . $e->getMessage());
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

        if (in_array($transaction->status, ['settlement', 'success', 'expire', 'cancel', 'denied'])) {
             Log::info("WEBHOOK_ALREADY_FINALIZED: Order ID {$orderId} already has status: {$transaction->status}");
             return response()->json(['message' => 'Transaction already finalized.'], 200);
        }

        try {
            DB::transaction(function () use ($transaction, $transactionStatus, $fraudStatus, $notification, $orderId) {

                $newStatus = $transaction->status;

                if ($transactionStatus === 'capture') {
                    $newStatus = ($fraudStatus === 'accept') ? 'settlement' : 'denied';
                } elseif ($transactionStatus === 'settlement') {
                    $newStatus = 'settlement';
                } elseif ($transactionStatus === 'pending') {
                    $newStatus = 'pending';
                } elseif (in_array($transactionStatus, ['deny', 'cancel'])) {
                    $newStatus = 'cancel';
                } elseif ($transactionStatus === 'expire') {
                    $newStatus = 'expire';
                }

                $transaction->status = $newStatus;
                $transaction->gateway_response = json_decode(json_encode($notification->getResponse()), true);

                if ($newStatus === 'settlement') {
                    $transaction->paid_at = Carbon::now();

                    $this->subscriptionService->activateSubscription($transaction);

                    Log::info("WEBHOOK_ACTIVATED_SUBSCRIPTION: User {$transaction->user_id} - TRX: {$orderId}");
                }

                $transaction->save();

                Log::info("WEBHOOK_STATUS_UPDATED: Order ID {$orderId} updated to {$newStatus}");
            });

            return response()->json(['message' => 'Notification processed successfully.'], 200);

        } catch (\Exception $e) {
            Log::error("WEBHOOK_PROCESSING_ERROR: Fatal error in DB transaction for Order ID: {$orderId}. " . $e->getMessage(), ['exception' => $e]);

            return response()->json(['message' => 'Internal Server Error during processing. Midtrans will retry.'], 500);
        }
    }
}
