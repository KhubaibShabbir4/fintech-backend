<?php
namespace App\Services;

use App\Models\Transaction;
use App\Services\Interfaces\TransactionServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService implements TransactionServiceInterface {
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator {
        $q = Transaction::query()->with('payment.merchant');

        if (!empty($filters['merchant_id'])) $q->whereHas('payment', fn($p)=>$p->where('merchant_id', $filters['merchant_id']));
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['date_from'])) $q->whereDate('created_at','>=',$filters['date_from']);
        if (!empty($filters['date_to'])) $q->whereDate('created_at','<=',$filters['date_to']);

        return $q->orderByDesc('id')->paginate($perPage);
    }

    public function show(int $id) { return Transaction::with('payment.refunds')->findOrFail($id); }
}
