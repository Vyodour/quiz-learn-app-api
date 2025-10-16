<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function activateSubscription(Transaction $transaction): ?Subscription
    {
        $plan = $transaction->plan;
        $user = $transaction->user;

        if (!$plan || !$user) {
            Log::error("SUBSCRIPTION_SERVICE_ERROR: Plan atau User tidak ditemukan untuk Transaksi: {$transaction->id}");
            return null;
        }

        $durationDays = $plan->duration_days ?? 30;

        $activeSubscription = $user->getActiveSubscription();

        if ($activeSubscription) {
            $startDate = $activeSubscription->ends_at;
            $newEndsAt = $startDate->copy()->addDays($durationDays);

            $activeSubscription->update([
                'plan_id' => $plan->id,
                'ends_at' => $newEndsAt,
            ]);

            $transaction->update(['subscription_id' => $activeSubscription->id]);

            Log::info("SUBSCRIPTION_RENEWED: User ID: {$user->id}. Diperbarui hingga: {$newEndsAt->format('Y-m-d')}");
            return $activeSubscription;

        } else {
            $newEndsAt = Carbon::now()->addDays($durationDays);

            $newSubscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'ends_at' => $newEndsAt,
                'status' => 'active',
                'gateway_subscription_id' => null,
                'payment_method' => $transaction->payment_gateway,
            ]);

            $transaction->update(['subscription_id' => $newSubscription->id]);

            Log::info("SUBSCRIPTION_CREATED: User ID: {$user->id}. Aktif hingga: {$newEndsAt->format('Y-m-d')}");
            return $newSubscription;
        }
    }
}
