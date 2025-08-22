<?php

namespace App\Services;

use App\Models\{Payment, Transaction, CheckoutSession, WebhookLog, Refund, Merchant};
use App\Services\Interfaces\PaymentServiceInterface;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Stripe\Webhook;

class PaymentService implements PaymentServiceInterface {
    public function __construct(private ?StripeClient $stripe = null) {
        if ($this->stripe === null) {
            $apiKey = config('services.stripe.secret') ?? env('STRIPE_SECRET');
            if (empty($apiKey)) {
                throw new \RuntimeException('Stripe secret is not configured. Set STRIPE_SECRET or services.stripe.secret.');
            }
            $this->stripe = new StripeClient($apiKey);
        }
    }

    public function createCheckout(array $data): array {
        // create our payment + session
        $reference = 'ref_'.Str::random(24);

        $payment = Payment::create([
            'merchant_id' => $data['merchant_id'],
            'provider' => 'stripe',
            'currency' => $data['currency'] ?? 'usd',
            'amount'   => $data['amount'],
            'method'   => $data['method'],
            'status'   => 'pending',
            'reference'=> $reference,
            'metadata' => [
                'customer' => $data['customer'] ?? null,
                'cart'     => $data['cart'] ?? null,
            ],
        ]);

        Transaction::create([
            'payment_id'=>$payment->id,
            'customer_name'=>$data['customer']['name'] ?? null,
            'customer_email'=>$data['customer']['email'] ?? null,
            'customer_phone'=>$data['customer']['phone'] ?? null,
            'country_code'=>null,
            'amount'=>$payment->amount,
            'status'=>'initiated',
            'extra'=>['ip'=>request()->ip(),'ua'=>request()->userAgent()],
        ]);

        $session = CheckoutSession::create([
            'merchant_id'=>$payment->merchant_id,
            'session_ref'=>Str::uuid()->toString(),
            'amount'=>$payment->amount,
            'currency'=>$payment->currency,
            'method'=>$payment->method,
            'return_url_success'=>$data['return_url_success'] ?? null,
            'return_url_failure'=>$data['return_url_failure'] ?? null,
            'status'=>'open',
            'customer'=>$data['customer'] ?? null,
            'cart'=>$data['cart'] ?? null,
        ]);

        // Create PaymentIntent in Stripe
        $intent = $this->stripe->paymentIntents->create([
            'amount' => (int) round($payment->amount * 100),
            'currency' => $payment->currency,
            'metadata' => ['reference' => $payment->reference, 'payment_id'=>$payment->id],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        $payment->update(['provider_payment_id' => $intent->id]);

        return [
            'reference' => $payment->reference,
            'client_secret' => $intent->client_secret,
            'session_ref' => $session->session_ref,
        ];
    }

    public function getPaymentStatusByReference(string $reference): Payment {
        return Payment::where('reference',$reference)->firstOrFail();
    }

    public function handleWebhook(array $headers, string $payload): void {
        $secret = env('STRIPE_WEBHOOK_SECRET');
        $sig = $headers['stripe-signature'] ?? ($headers['Stripe-Signature'] ?? null);

        // Verify signature
        $event = Webhook::constructEvent($payload, $sig, $secret);

        WebhookLog::firstOrCreate(
            ['event_id'=>$event->id],
            ['provider'=>'stripe','type'=>$event->type,'payload'=>$event->toArray(),'processed'=>false]
        );

        $data = $event->data->object ?? null;
        if (!$data) return;

        // PaymentIntent events
        if (str_starts_with($event->type, 'payment_intent.')) {
            $pi = $data;
            $payment = Payment::where('provider_payment_id', $pi->id)->first();
            if (!$payment) return;

            if ($event->type === 'payment_intent.succeeded') {
                $payment->status = 'succeeded';
                $payment->save();
                $payment->transaction?->update(['status'=>'succeeded']);
            }
            if ($event->type === 'payment_intent.payment_failed') {
                $payment->status = 'failed';
                $payment->save();
                $payment->transaction?->update(['status'=>'failed']);
            }
        }

        // Mark processed
        WebhookLog::where('event_id', $event->id)->update(['processed'=>true]);
    }

    public function refund(int $paymentId, float $amount, ?string $reason = null) {
        $payment = Payment::findOrFail($paymentId);
        if ($payment->status !== 'succeeded') abort(422, 'Only succeeded payments can be refunded.');
        // Stripe refund
        $stripeRefund = $this->stripe->refunds->create([
            'payment_intent' => $payment->provider_payment_id,
            'amount' => (int) round($amount * 100),
            'metadata' => ['reference'=>$payment->reference, 'reason'=>$reason],
        ]);

        $refund = $payment->refunds()->create([
            'provider_refund_id'=>$stripeRefund->id,
            'amount'=>$amount,
            'status'=>($stripeRefund->status === 'succeeded') ? 'succeeded' : 'pending',
            'reason'=>$reason
        ]);

        if ($refund->status === 'succeeded') {
            $payment->status = 'refunded';
            $payment->save();
            $payment->transaction?->update(['status'=>'refunded']);
        }

        return $refund;
    }
}
