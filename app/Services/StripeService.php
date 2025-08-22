<?php

namespace App\Services;

use Stripe\StripeClient;

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
        string $merchantStripeAccountId,
        string $successUrl,
        string $cancelUrl
    ): array {
        $session = $this->client->checkout->sessions->create([
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
            'payment_intent_data' => [
                'transfer_data' => [
                    'destination' => $merchantStripeAccountId,
                ],
                // 'application_fee_amount' => 0, // set if you charge a fee
            ],
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
            'payment_intent' => $session->payment_intent ?? null,
        ];
    }

    public function refund(string $paymentIntentId, ?float $amount = null): array
    {
        $payload = ['payment_intent' => $paymentIntentId];
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
}


