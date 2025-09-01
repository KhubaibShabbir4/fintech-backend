<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller {
    public function success(Request $request) {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return response()->view('payments.success', [
                'success' => false,
                'message' => 'Missing session_id'
            ]);
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId, ['expand' => ['payment_intent']]);
        } catch (\Throwable $e) {
            Log::error('Stripe session retrieve failed', ['error' => $e->getMessage(), 'session_id' => $sessionId]);
            return response()->view('payments.success', [
                'success' => false,
                'message' => 'Unable to verify payment session.'
            ]);
        }

        $transaction = Transaction::where('stripe_session_id', $sessionId)->with('payment')->first();
        $payment = $transaction?->payment;

        $isPaid = false;
        $paymentIntentStatus = $session->payment_intent->status ?? null;
        if (($session->status ?? null) === 'complete' || $paymentIntentStatus === 'succeeded') {
            $isPaid = true;
        }

        if ($payment) {
            if ($isPaid) {
                $payment->status = 'paid';
                if (!$payment->provider_payment_id && isset($session->payment_intent->id)) {
                    $payment->provider_payment_id = $session->payment_intent->id;
                }
                $payment->save();

                if ($transaction) {
                    $transaction->status = 'success';
                    $transaction->save();
                }
            }
        }

        return response()->view('payments.success', [
            'success' => $isPaid,
            'payment' => $payment,
            'session' => $session,
            'message' => $isPaid ? 'Payment successful.' : 'Payment not completed yet.'
        ]);
    }

    public function cancel() {
        return response()->view('payments.cancel');
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sig = $request->headers->get('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');
    
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            Log::error('❌ Stripe Webhook signature verification failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return response()->json(['status' => 'invalid'], 400);
        }
    
        $type   = $event->type;
        $object = $event->data->object ?? null;
    
        Log::info("✅ Stripe webhook received", ['type' => $type, 'object' => $object]);
    
        if ($type === 'checkout.session.completed') {
            $sessionId = $object->id ?? null;
            $pi        = $object->payment_intent ?? null;
    
            if ($sessionId) {
                $tx = Transaction::where('stripe_session_id', $sessionId)->with('payment')->first();
    
                if ($tx) {
                    $tx->status = 'success';
                    if ($pi && !$tx->stripe_payment_intent) {
                        $tx->stripe_payment_intent = $pi;
                    }
                    $tx->save();
    
                    if ($tx->payment) {
                        $tx->payment->update([
                            'status' => 'paid',
                            'provider_payment_id' => $pi ?? $tx->payment->provider_payment_id,
                        ]);
                    } else {
                        Log::warning("⚠️ Transaction found but no related Payment", ['tx_id' => $tx->id]);
                    }
                } else {
                    Log::warning("⚠️ No Transaction found for checkout.session.completed", [
                        'session_id' => $sessionId,
                        'pi' => $pi,
                    ]);
                }
            }
        }
    
        if ($type === 'payment_intent.succeeded') {
            $pi = $object->id ?? null;
    
            if ($pi) {
                $tx = Transaction::where('stripe_payment_intent', $pi)->with('payment')->first();
    
                if ($tx) {
                    $tx->update(['status' => 'success']);
    
                    if ($tx->payment) {
                        $tx->payment->update([
                            'status' => 'paid',
                            'provider_payment_id' => $pi,
                        ]);
                    } else {
                        Log::warning("⚠️ Transaction found but no related Payment", ['tx_id' => $tx->id]);
                    }
                } else {
                    Log::warning("⚠️ No Transaction found for payment_intent.succeeded", ['pi' => $pi]);
                }
            }
        }
    
        return response()->json(['status' => 'ok']);
    }
}    


