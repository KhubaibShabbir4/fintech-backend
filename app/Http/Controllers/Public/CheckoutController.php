<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\Interfaces\PaymentServiceInterface;
use App\Models\Merchant;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private PaymentServiceInterface $payments,
        private StripeService $stripe
    ) {}

    /**
     * 🚀 Create Checkout Session
     */
    public function checkout(CheckoutRequest $request)
    {
        $data = $request->validated();
        $merchant = Merchant::findOrFail($data['merchant_id']);

        if (!$merchant->stripe_account_id) {
            return response()->json(['message' => 'Merchant is not onboarded to Stripe.'], 422);
        }

        $successUrl = $data['return_url_success'] ?? url('/success');
        $cancelUrl  = $data['return_url_failure'] ?? url('/cancel');

        $session = $this->stripe->createCheckoutSession(
            (float) $data['amount'],
            $data['currency'] ?? 'usd',
            $merchant->stripe_account_id,
            $successUrl,
            $cancelUrl
        );

        // Save Payment (pending)
        $payment = Payment::create([
            'merchant_id' => $merchant->id,
            'provider' => 'stripe',
            'provider_payment_id' => null, // will be updated later
            'currency' => $data['currency'] ?? 'usd',
            'amount' => (float) $data['amount'],
            'method' => $data['method'],
            'status' => 'pending',
            'reference' => 'ref_' . bin2hex(random_bytes(8)),
            'metadata' => [
                'customer' => $data['customer'] ?? null,
                'cart' => $data['cart'] ?? null,
            ],
        ]);

        // Save Transaction (pending)
        Transaction::create([
            'payment_id' => $payment->id,
            'merchant_id' => $merchant->id,
            'customer_name' => $data['customer']['name'] ?? null,
            'customer_email' => $data['customer']['email'] ?? null,
            'customer_phone' => $data['customer']['phone'] ?? null,
            'country_code' => null,
            'amount' => $payment->amount,
            'status' => 'pending',
            'extra' => ['ip' => $request->ip(), 'ua' => $request->userAgent()],
            'stripe_payment_intent' => null,               // will be filled later by webhook
            'stripe_session_id' => $session['id'] ?? null, // save session_id now
            'stripe_charge_id' => null,
        ]);

        return response()->json([
            'url' => $session['url'],
            'session_id' => $session['id'],
        ]);
    }

    /**
     * 🔔 Stripe Webhook Handler
     */
   /**
 * 🔔 Stripe Webhook Handler
 */
/*public function webhook(Request $request)
{
    $payload = $request->getContent();
    $sig     = $request->headers->get('Stripe-Signature');
    $secret  = config('services.stripe.webhook_secret');

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
    } catch (\Throwable $e) {
        Log::error('❌ Stripe Webhook signature verification failed', [
            'error' => $e->getMessage(),
            'payload' => $payload
        ]);
        return response()->json(['message' => 'Invalid signature'], 400);
    }

    $type   = $event->type;
    $object = $event->data->object ?? null;

    if (!$object) {
        return response()->json(['ok' => true]);
    }

    Log::info('✅ Stripe Webhook received', [
        'type'   => $type,
        'object' => $object,
    ]);

    // helper to find related transaction
    $findTransaction = function ($pi = null, $sessionId = null, $chargeId = null) {
        return Transaction::when($pi, fn($q) => $q->orWhere('stripe_payment_intent', $pi))
            ->when($sessionId, fn($q) => $q->orWhere('stripe_session_id', $sessionId))
            ->when($chargeId, fn($q) => $q->orWhere('stripe_charge_id', $chargeId))
            ->first();
    };

    
     * 🟢 PaymentIntent succeeded (most reliable)
     
    if ($type === 'payment_intent.succeeded') {
        $pi = $object->id;
        $tx = $findTransaction($pi);

        if ($tx) {
            $tx->update([
                'status' => 'success',
                'stripe_payment_intent' => $pi,
            ]);

            if ($tx->payment) {
                $tx->payment->update([
                    'status' => 'success',
                    'provider_payment_id' => $pi,
                ]);
            }
        } else {
            Log::warning('⚠️ Transaction not found for payment_intent.succeeded', [
                'pi' => $pi,
            ]);
        }
    }

    
     * 🟢 Checkout Session Completed (sometimes no PI yet)
     
    if ($type === 'checkout.session.completed') {
        $sessionId = $object->id;
        $pi        = $object->payment_intent ?? null;

        $tx = $findTransaction($pi, $sessionId);
        if ($tx) {
            $tx->update([
                'status' => 'success',
                'stripe_payment_intent' => $pi,
            ]);

            if ($tx->payment) {
                $tx->payment->update([
                    'status' => 'success',
                    'provider_payment_id' => $pi,
                ]);
            }
        } else {
            Log::warning('⚠️ Transaction not found for checkout.session.completed', [
                'pi' => $pi,
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * 🟢 Charge Succeeded (backup)
     
    if ($type === 'charge.succeeded') {
        $chargeId = $object->id;
        $pi = $object->payment_intent ?? null;

        $tx = $findTransaction($pi, null, $chargeId);
        if ($tx) {
            $tx->update([
                'status' => 'success',
                'stripe_payment_intent' => $pi,
                'stripe_charge_id' => $chargeId,
            ]);

            if ($tx->payment) {
                $tx->payment->update([
                    'status' => 'success',
                    'provider_payment_id' => $pi,
                ]);
            }
        } else {
            Log::warning('⚠️ Transaction not found for charge.succeeded', [
                'pi' => $pi,
                'charge_id' => $chargeId,
            ]);
        }
    }

    return response()->json(['ok' => true]);
}
*/

    /**
     * 🔍 Check status by reference
     */
    public function status(string $reference)
    {
        return response()->json(
            $this->payments->getPaymentStatusByReference($reference)
        );
    }
}
