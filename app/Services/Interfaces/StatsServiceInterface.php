<?php


namespace App\Services\Interfaces;

interface StatsServiceInterface {
    public function revenue(array $filters = []);
    public function methods(array $filters = []);
    public function transactionsAggregate(array $filters = []);
}
