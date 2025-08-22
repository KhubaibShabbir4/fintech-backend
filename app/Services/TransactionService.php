<?php
namespace App\Services;

use App\Models\Transaction;
use App\Services\Interfaces\TransactionServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;

class TransactionService implements TransactionServiceInterface {
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator {
        $query = Transaction::query()
            ->with(['merchant', 'payment.merchant']);

        // Date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Status filter (normalize possible "succeeded" -> "success")
        if (!empty($filters['status'])) {
            $status = $filters['status'] === 'succeeded' ? 'success' : $filters['status'];
            $query->where('status', $status);
        }

        // Merchant filter (admin only - merchants already scoped at controller level)
        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        // Customer filter (apply only if the column exists on transactions or payments)
        if (!empty($filters['customer_id'])) {
            $customerId = $filters['customer_id'];

            if (Schema::hasColumn('transactions', 'customer_id')) {
                $query->where('customer_id', $customerId);
            } elseif (Schema::hasColumn('payments', 'customer_id')) {
                $query->whereHas('payment', function ($q) use ($customerId) { $q->where('customer_id', $customerId); });
            }
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show(int $id) { return Transaction::with('payment.refunds')->findOrFail($id); }

    public function export(array $filters = []): LazyCollection {
        $query = Transaction::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $status = $filters['status'] === 'succeeded' ? 'success' : $filters['status'];
            $query->where('status', $status);
        }
        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }
        if (!empty($filters['customer_id'])) {
            $customerId = $filters['customer_id'];
            if (Schema::hasColumn('transactions', 'customer_id')) {
                $query->where('customer_id', $customerId);
            } elseif (Schema::hasColumn('payments', 'customer_id')) {
                $query->whereHas('payment', function ($q) use ($customerId) { $q->where('customer_id', $customerId); });
            }
        }

        $query->orderByDesc('id');

        return LazyCollection::make(function () use ($query) {
            foreach ($query->cursor() as $tx) {
                yield [
                    'id' => $tx->id,
                    'payment_id' => $tx->payment_id,
                    'merchant_id' => $tx->merchant_id,
                    'customer_name' => $tx->customer_name,
                    'customer_email' => $tx->customer_email,
                    'customer_phone' => $tx->customer_phone,
                    'country_code' => $tx->country_code,
                    'amount' => $tx->amount,
                    'status' => $tx->status,
                    'stripe_payment_intent' => $tx->stripe_payment_intent,
                    'stripe_session_id' => $tx->stripe_session_id,
                    'created_at' => optional($tx->created_at)->toDateTimeString(),
                ];
            }
        });
    }
}
