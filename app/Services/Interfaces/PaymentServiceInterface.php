<?php

namespace App\Services\Interfaces;

use App\Models\Payment;
use App\Models\CheckoutSession;

interface PaymentServiceInterface {
    public function createCheckout( array $data): array; // returns session + payment refs
    public function getPaymentStatusByReference(string $reference): Payment;
    public function handleWebhook(array $headers, string $payload): void;
    public function refund(int $paymentId, float $amount, ?string $reason = null);
}
