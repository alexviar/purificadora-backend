<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatuses;
use App\Models\PurchaseStatus;
use App\Models\SupplyPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $endpointSecret = config('services.stripe.webhook_secret'); // Define este valor en tu archivo .env

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            // Verifica la firma del webhook
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $this->processEvent($event);


        return response()->json(['status' => 'success'], 200);
    }

    function processEvent(Event $event)
    {
        Log::info('Stripe webhook event', $event->toArray());
        switch ($event->type) {
            case 'payment_intent.succeeded':
                /** @var PaymentIntent $paymentIntent */
                $paymentIntent = $event->data->object;
                $purchaseId = $paymentIntent->metadata->purchase_id ?? null;
                if (!$purchaseId) return;

                $purchase = SupplyPurchase::find($purchaseId);
                if (!$purchase || $purchase->status != PurchaseStatuses::PENDING_PAYMENT->value) return;

                $purchase->status = PurchaseStatuses::PAID->value;
                $purchase->save();
                break;

            // case 'charge.updated':
            // /** @var Charge $charge */
            // $charge = $event->data->object;
            // $purchaseId = $charge->metadata->purchase_id ?? null;
            // if (!$purchaseId) return;

            // $purchase = PurchaseStatus::find($purchaseId);
            // if (!$purchase) return;

            // Stripe::setApiKey(config('services.stripe.secret_key'));
            // $balanceTransaction = BalanceTransaction::retrieve($charge->balance_transaction);
            // $purchase->payment()->create([
            //     'date'  => Date::createFromTimestamp($charge->created, config('app.timezone')),
            //     'amount' => $balanceTransaction->amount / 100,
            //     'method' => 2,
            //     'fee' => $balanceTransaction->fee / 100,
            //     'transaction_id' => $balanceTransaction->id,
            // ]);
            // break;

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
        }
    }
}
