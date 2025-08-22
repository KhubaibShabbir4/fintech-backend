<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\TransactionServiceInterface;
use App\Services\Interfaces\PaymentServiceInterface;
use App\Services\StripeService;
use App\Http\Requests\RefundRequest;
use App\Models\Payment;
use Illuminate\Http\Request;

class TransactionController extends Controller {
    public function __construct(
        private TransactionServiceInterface $tx,
        private PaymentServiceInterface $payments,
        private StripeService $stripe
    ) {}

    public function index(Request $request) {
        $filters = $request->only('merchant_id','status','date_from','date_to');
        return response()->json($this->tx->list($filters, (int)($request->per_page ?? 15)));
    }

    public function show(int $id) { return response()->json($this->tx->show($id)); }

    public function refund(RefundRequest $request, int $paymentId) {
        $data = $request->validated();
        $payment = Payment::findOrFail($paymentId);
        if ($payment->provider !== 'stripe') {
            abort(422, 'Only Stripe refunds are supported here.');
        }
        if (! $payment->provider_payment_id) {
            abort(422, 'Payment intent not found on payment.');
        }

        $refund = $this->stripe->refund($payment->provider_payment_id, (float)$data['amount']);

        // Update DB
        $payment->refunds()->create([
            'provider_refund_id' => $refund['id'],
            'amount' => (float)$data['amount'],
            'status' => $refund['status'] === 'succeeded' ? 'succeeded' : 'pending',
            'reason' => $data['reason'] ?? null,
        ]);

        if ($refund['status'] === 'succeeded') {
            $payment->status = 'refunded';
            $payment->save();
            $payment->transaction?->update(['status' => 'refunded']);
        }

        return response()->json($refund);
    }
}
