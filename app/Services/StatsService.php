<?php

namespace App\Services;

use App\Models\Payment;
use App\Services\Interfaces\StatsServiceInterface;
use Illuminate\Support\Facades\DB;

class StatsService implements StatsServiceInterface
{
    public function revenue(array $filters = [])
    {
        $q = Payment::query()->where('status', 'succeeded');

        if (!empty($filters['merchant_id'])) {
            $q->where('merchant_id', $filters['merchant_id']);
        }

        if (!empty($filters['range']) && in_array($filters['range'], ['daily', 'weekly', 'monthly'])) {
            $format = [
                'daily'   => '%Y-%m-%d',    // e.g. 2025-08-19
                'weekly'  => '%Y-%u',       // year-week, e.g. 2025-34
                'monthly' => '%Y-%m'        // year-month, e.g. 2025-08
            ][$filters['range']];

            return $q->selectRaw("DATE_FORMAT(created_at, '{$format}') as bucket, SUM(amount) as total")
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get();
        }

        return [
            'total' => $q->sum('amount'),
            'count' => $q->count(),
        ];
    }

    public function methods(array $filters = [])
    {
        $q = Payment::query()->where('status', 'succeeded');

        if (!empty($filters['merchant_id'])) {
            $q->where('merchant_id', $filters['merchant_id']);
        }

        return $q->select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))
            ->groupBy('method')
            ->orderByDesc('count')
            ->get();
    }

    public function transactionsAggregate(array $filters = [])
    {
        $q = Payment::query();

        if (!empty($filters['merchant_id'])) {
            $q->where('merchant_id', $filters['merchant_id']);
        }

        return [
            'succeeded' => (clone $q)->where('status', 'succeeded')->count(),
            'failed'    => (clone $q)->where('status', 'failed')->count(),
            'pending'   => (clone $q)->where('status', 'pending')->count(),
            'refunds'   => DB::table('refunds')->count(),
        ];
    }
}
