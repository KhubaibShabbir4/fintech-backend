<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\Interfaces\PaymentServiceInterface;
use Illuminate\Http\Request;

class CheckoutController extends Controller {
    public function __construct(private PaymentServiceInterface $payments) {}

    // Public — customers
    public function checkout(CheckoutRequest $request) {
        $session = $this->payments->createCheckout($request->validated());
        return response()->json($session);
    }

    // Public — webhook (Stripe)
    public function webhook(Request $request) {
        $this->payments->handleWebhook($request->headers->all(), $request->getContent());
        return response()->json(['ok'=>true]);
    }

    // Public — status by reference (merchant can also poll it)
    public function status(string $reference) {
        return response()->json($this->payments->getPaymentStatusByReference($reference));
    }
}
