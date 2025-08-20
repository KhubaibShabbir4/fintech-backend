<?php

namespace App\Services\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionServiceInterface {
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function show(int $id);
}
