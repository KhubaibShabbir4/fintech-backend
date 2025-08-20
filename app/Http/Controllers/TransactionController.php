<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\TransactionServiceInterface;
use App\Services\Interfaces\PaymentServiceInterface;
use App\Http\Requests\RefundRequest;
use Illuminate\Http\Request;

class TransactionController extends Controller {
    public function __construct(
        private TransactionServiceInterface $tx,
        private PaymentServiceInterface $payments
    ) {}

    public function index(Request $request) {
        $filters = $request->only('merchant_id','status','date_from','date_to');
        return response()->json($this->tx->list($filters, (int)($request->per_page ?? 15)));
    }

    public function show(int $id) { return response()->json($this->tx->show($id)); }

    public function refund(RefundRequest $request, int $paymentId) {
        $data = $request->validated();
        return response()->json($this->payments->refund($paymentId, (float)$data['amount'], $data['reason'] ?? null));
    }
}
