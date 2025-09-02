<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantUpdateRequest;
use App\Services\Interfaces\MerchantServiceInterface;
use App\Services\StripeService;
use Illuminate\Http\Request;

class  MerchantController extends Controller {
    public function __construct(private MerchantServiceInterface $service, private StripeService $stripe) {}

    public function store(Request $request) {
        $data = $request->validate([
            'business_name'=>'required|string|max:255',
            'logo'=>'nullable|image|max:2048',
            'bank_account_name'=>'nullable|string|max:255',
            'bank_account_number'=>'nullable|string|max:64',
            'bank_ifsc_swift'=>'nullable|string|max:64',
            'payout_preferences'=>'nullable|array',
        ]);
        $merchant = $this->service->registerForCurrentUser($data);

        if (empty($merchant->stripe_account_id)) {
            $stripeAccountId = $this->stripe->createMerchantAccount($merchant->user->email);
            $merchant->stripe_account_id = $stripeAccountId;
            $merchant->save();
        }

        $onboardingUrl = $this->stripe->createOnboardingLink($merchant->stripe_account_id);

        return response()->json([
            'merchant' => $merchant,
            'onboarding_url' => $onboardingUrl,
        ], 201);
    }

    public function onboardingLink(Request $request) {
        $user = $request->user();
        $merchant = $user->merchant ?? null;
        if (!$merchant) {
            abort(404, 'Merchant profile not found for user.');
        }

        if (empty($merchant->stripe_account_id)) {
            $stripeAccountId = $this->stripe->createMerchantAccount($user->email);
            $merchant->stripe_account_id = $stripeAccountId;
            $merchant->save();
        }

        $onboardingUrl = $this->stripe->createOnboardingLink($merchant->stripe_account_id);

        return response()->json(['onboarding_url' => $onboardingUrl]);
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

    public function update(MerchantUpdateRequest $request) {
        $merchantId = auth()->user()->merchant->id;
        return response()->json($this->service->updateForCurrentUser($merchantId, $request->validated()));
    }

    public function profile(Request $request) {
        $user = $request->user();
        $merchant = $user->merchant ?? null;
        if (!$merchant) {
            abort(404, 'Merchant profile not found for user.');
        }
        return response()->json($merchant->refresh());
    }
}
