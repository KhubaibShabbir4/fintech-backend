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
        $filters = $request->only('merchant_id','status','date_from','date_to','customer_id');

        // If the caller is a merchant, always scope to their merchant_id
        $user = $request->user();
        if ($user && $user->hasRole('merchant')) {
            $merchantId = $user->merchant?->id;
            if (!$merchantId) {
                abort(403, 'Merchant profile not found for user.');
            }
            $filters['merchant_id'] = $merchantId;
        }

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

    public function exportCsv(Request $request) {
        $filters = $request->only('merchant_id','status','date_from','date_to','customer_id');

        $user = $request->user();
        if ($user && $user->hasRole('merchant')) {
            $merchantId = $user->merchant?->id;
            if (!$merchantId) {
                abort(403, 'Merchant profile not found for user.');
            }
            $filters['merchant_id'] = $merchantId;
        }

        $filename = 'transactions_export.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'transactions_export_');
        
        $columns = [
            'id','payment_id','merchant_id','customer_name','customer_email','customer_phone','country_code','amount','status','stripe_payment_intent','stripe_session_id','created_at'
        ];

        // Write CSV to temporary file
        $out = fopen($tempFile, 'w');
        // BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $columns);

        foreach ($this->tx->export($filters) as $row) {
            $line = [];
            foreach ($columns as $col) { $line[] = $row[$col] ?? ''; }
            fputcsv($out, $line);
        }
        fclose($out);

        // Return file download response
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }
}
