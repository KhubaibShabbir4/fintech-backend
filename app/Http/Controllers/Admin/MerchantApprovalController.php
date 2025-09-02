<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\MerchantServiceInterface;
use App\Models\Merchant;
use Illuminate\Http\Request;


class MerchantApprovalController extends Controller {
    public function __construct(private MerchantServiceInterface $service) {}

    public function dashboard()
    {
        // Example: show pending merchants
        return response()->json([
            'pending_merchants' => Merchant::where('status', 'pending')->get()
        ]);
    }

    public function approveMerchant($id, Request $request)
    {
        $merchant = Merchant::findOrFail($id);

        // Map approved → verified
        $merchant->status = 'verified';
        $merchant->save();

        return response()->json([
            'message' => "Merchant ID {$id} approved successfully",
            'merchant' => $merchant
        ]);
    }

    public function allTransactions()
    {
        // TODO: fetch transactions from DB
        return response()->json(['transactions' => []]);
    }
    public function setStatus(Request $request, int $id) {
        $data = $request->validate(['status'=>'required|in:verified,rejected']);
        return response()->json($this->service->setApproval($id, $data['status']));
    }
}
