<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\StatsServiceInterface;
use Illuminate\Http\Request;

class StatsController extends Controller {
    public function __construct(private StatsServiceInterface $stats) {}

    public function revenue(Request $request) {
        return response()->json($this->stats->revenue($request->only('merchant_id','range')));
    }
    public function methods(Request $request) {
        return response()->json($this->stats->methods($request->only('merchant_id')));
    }
    public function transactions(Request $request) {
        return response()->json($this->stats->transactionsAggregate($request->only('merchant_id')));
    }
}
