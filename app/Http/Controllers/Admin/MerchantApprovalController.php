<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\MerchantServiceInterface;
use App\Models\Merchant;
use App\Models\User;
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

    public function rejectMerchant($id, Request $request)
    {
        $merchant = $this->service->setApproval($id, 'rejected');

        return response()->json([
            'message' => "Merchant ID {$id} rejected successfully",
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

    /**
     * Get all merchants with their user information
     * Only accessible by admin users
     */
    public function getAllMerchants()
    {
        // Fetch ALL users with merchant role (includes those without a verified profile)
        $merchants = User::role('merchant', 'api')
            ->with(['merchant:id,user_id,business_name,status,created_at'])
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($user) {
                $merchant = $user->merchant; // may be null if profile not created yet
                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'merchant_id' => $merchant?->id,
                    'business_name' => $merchant?->business_name,
                    // status will be null if no merchant profile exists yet
                    'status' => $merchant?->status,
                    'registered_at' => $merchant?->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $merchants,
            'total' => $merchants->count()
        ]);
    }
}
