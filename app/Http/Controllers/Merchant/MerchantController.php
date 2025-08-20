<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantUpdateRequest;
use App\Services\Interfaces\MerchantServiceInterface;
use Illuminate\Http\Request;

class  MerchantController extends Controller {
    public function __construct(private MerchantServiceInterface $service) {}

    public function store(Request $request) {
        $data = $request->validate([
            'business_name'=>'required|string|max:255',
            'logo'=>'nullable|image|max:2048',
            'bank_account_name'=>'nullable|string|max:255',
            'bank_account_number'=>'nullable|string|max:64',
            'bank_ifsc_swift'=>'nullable|string|max:64',
            'payout_preferences'=>'nullable|array',
        ]);
        return response()->json($this->service->registerForCurrentUser($data), 201);
    }
    public function approveMerchant(int $id)
    {
        $merchant = $this->MerchantService->setApproval($id, 'verified');

        return response()->json([
            'message' => "Merchant {$id} approved successfully",
            'merchant' => $merchant
        ]);
    }

    public function show(int $id) { return response()->json($this->service->get($id)); }

    public function update(MerchantUpdateRequest $request, int $id) {
        return response()->json($this->service->updateForCurrentUser($id, $request->validated()));
    }
}
