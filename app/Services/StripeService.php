<?php

namespace App\Services;

use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class StripeService
{
    private StripeClient $client;

    public function __construct(?StripeClient $client = null)
    {
        $this->client = $client ?? new StripeClient(config('services.stripe.secret'));
    }

    public function createMerchantAccount(string $email): string
    {
        $account = $this->client->accounts->create([
            'type' => 'express',
            'email' => $email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
        ]);

        return $account->id;
    }

    public function createOnboardingLink(string $accountId): string
    {
        $link = $this->client->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => url('/onboarding/refresh'),
            'return_url' => url('/onboarding/return'),
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    public function createCheckoutSession(
        float $amount,
        string $currency,
        ?string $merchantStripeAccountId,
        string $successUrl,
        string $cancelUrl
    ): array {
        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'product_data' => [
                        'name' => 'Checkout',
                    ],
                    'unit_amount' => (int) round($amount * 100),
                ],
                'quantity' => 1,
            ]],
        ];

        $options = []; // extra opts like ['stripe_account' => acct_xxx]

        // ✅ Only attempt transfers if explicitly enabled & merchant ID is provided
        if (
            config('services.stripe.use_transfers', false) &&
            !empty($merchantStripeAccountId)
        ) {
            try {
                // Retrieve account to check capabilities
                $account = $this->client->accounts->retrieve($merchantStripeAccountId, []);

                if (
                    isset($account->capabilities->transfers) &&
                    $account->capabilities->transfers === 'active'
                ) {
                    // Direct-to-merchant flow
                    $payload['payment_intent_data'] = [
                        'transfer_data' => [
                            'destination' => $merchantStripeAccountId,
                        ],
                        // 'application_fee_amount' => 200, // optional platform fee
                    ];
                } else {
                    Log::warning("Merchant account {$merchantStripeAccountId} has no active transfers capability. Falling back to platform checkout.");
                }
            } catch (\Throwable $e) {
                Log::error("Failed retrieving merchant account {$merchantStripeAccountId}: " . $e->getMessage());
            }
        }

        $session = $this->client->checkout->sessions->create($payload, $options);

        Log::info("Stripe CheckoutSession created", [
            'session_id' => $session->id,
            'pi' => $session->payment_intent,
            'merchant_account' => $merchantStripeAccountId,
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
            'payment_intent' => $session->payment_intent ?? null,
        ];
    }

    /**
     * 🔄 Refund PaymentIntent
     */
    public function refund(string $paymentIntentId, ?float $amount = null): array
    {
        $payload = [
            'payment_intent' => $paymentIntentId,
            'reverse_transfer' => true,
            'refund_application_fee' => true,
        ];

        if ($amount !== null) {
            $payload['amount'] = (int) round($amount * 100);
        }

        $refund = $this->client->refunds->create($payload);

        return [
            'id' => $refund->id,
            'status' => $refund->status,
            'amount' => isset($refund->amount) ? ((float) $refund->amount) / 100.0 : null,
        ];
    }

    /**
     * 🔄 Refund by Checkout Session ID
     */
    public function refundByCheckoutSessionId(string $checkoutSessionId, ?float $amount = null): array
    {
        $session = $this->client->checkout->sessions->retrieve(
            $checkoutSessionId,
            ['expand' => ['payment_intent']]
        );

        $paymentIntentId = null;
        if (isset($session->payment_intent)) {
            $paymentIntentId = is_string($session->payment_intent)
                ? $session->payment_intent
                : ($session->payment_intent->id ?? null);
        }

        if (!$paymentIntentId) {
            throw new \RuntimeException('Unable to determine payment_intent from checkout session.');
        }

        return $this->refund($paymentIntentId, $amount);
    }
}
