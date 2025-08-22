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

    // ✅ Checkout endpoint (customers)
    public function checkout(CheckoutRequest $request)
    {
        $data = $request->validated();
        $merchant = Merchant::findOrFail($data['merchant_id']);

        if (!$merchant->stripe_account_id) {
            return response()->json(['message' => 'Merchant is not onboarded to Stripe.'], 422);
        }

        $successUrl = $data['return_url_success'] ?? url('/success');
        $cancelUrl = $data['return_url_failure'] ?? url('/cancel');

        $session = $this->stripe->createCheckoutSession(
            (float)$data['amount'],
            $data['currency'] ?? 'usd',
            $merchant->stripe_account_id,
            $successUrl,
            $cancelUrl
        );

        $payment = Payment::create([
            'merchant_id' => $merchant->id,
            'provider' => 'stripe',
            'provider_payment_id' => $session['payment_intent'] ?? null,
            'currency' => $data['currency'] ?? 'usd',
            'amount' => (float)$data['amount'],
            'method' => $data['method'],
            'status' => 'pending',
            'reference' => 'ref_' . bin2hex(random_bytes(8)),
            'metadata' => [
                'customer' => $data['customer'] ?? null,
                'cart' => $data['cart'] ?? null,
            ],
        ]);

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
            'stripe_payment_intent' => $session['payment_intent'] ?? null,
            'stripe_session_id' => $session['id'] ?? null,
        ]);

        return response()->json(['url' => $session['url'], 'session_id' => $session['id']]);
    }

    // ✅ Webhook endpoint (Stripe → our app)
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sig = $request->headers->get('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            Log::error('Stripe Webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $type = $event->type;
        $object = $event->data->object ?? null;

        if (!$object) {
            return response()->json(['ok' => true]);
        }

        Log::info('Stripe Webhook received', ['type' => $type]);

        // 🔎 Helper: find transaction by PI or session
        $findTransaction = function ($pi = null, $sessionId = null) {
            return Transaction::where('stripe_payment_intent', $pi)
                ->orWhere('stripe_session_id', $sessionId)
                ->first();
        };

        // ✅ Case 1: Checkout session completed
        if ($type === 'checkout.session.completed') {
            $sessionId = $object->id;
            $pi = $object->payment_intent ?? null;

            $tx = $findTransaction($pi, $sessionId);
            if ($tx) {
                $tx->status = 'success';
                if ($pi) {
                    $tx->stripe_payment_intent = $pi; // backfill if missing
                }
                $tx->save();

                $tx->payment?->update([
                    'status' => 'success',
                    'provider_payment_id' => $pi ?? $tx->payment->provider_payment_id,
                ]);

                Log::info('Transaction updated (checkout.session.completed)', ['tx_id' => $tx->id]);
            }
        }

        // ✅ Case 2: Payment intent succeeded or failed
        if ($type === 'payment_intent.succeeded' || $type === 'payment_intent.payment_failed') {
            $pi = $object->id;
            $tx = $findTransaction($pi);

            if ($tx) {
                $status = $type === 'payment_intent.succeeded' ? 'success' : 'failed';
                $tx->status = $status;
                $tx->stripe_payment_intent = $pi;
                $tx->save();

                $tx->payment?->update([
                    'status' => $status,
                    'provider_payment_id' => $pi,
                ]);

                Log::info('Transaction updated (payment_intent)', ['tx_id' => $tx->id, 'status' => $status]);
            }
        }

        // ✅ Case 3: Charge events
        if ($type === 'charge.succeeded' || $type === 'charge.updated') {
            $pi = $object->payment_intent ?? null;
            $tx = $findTransaction($pi);

            if ($tx) {
                $tx->status = 'success';
                $tx->stripe_payment_intent = $pi ?? $tx->stripe_payment_intent;
                $tx->save();

                $tx->payment?->update([
                    'status' => 'success',
                    'provider_payment_id' => $pi,
                ]);

                Log::info('Transaction updated (charge)', ['tx_id' => $tx->id]);
            }
        }

        return response()->json(['ok' => true]);
    }

    // ✅ Status by reference (merchants can poll this)
    public function status(string $reference)
    {
        return response()->json($this->payments->getPaymentStatusByReference($reference));
    }
}
