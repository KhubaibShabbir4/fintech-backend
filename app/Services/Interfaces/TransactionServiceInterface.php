<?php

namespace App\Services\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionServiceInterface {
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function show(int $id);
    /**
     * Return a lazy iterable of transactions matching filters for CSV export.
     */
    public function export(array $filters = []): \Illuminate\Support\LazyCollection;
}
