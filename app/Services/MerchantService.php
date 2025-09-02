<?php
namespace App\Services;

use App\Models\Merchant;
use App\Services\Interfaces\MerchantServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MerchantService implements MerchantServiceInterface {
    public function registerForCurrentUser(array $data): Merchant {
        $logoPath = null;
        if (!empty($data['logo']) && $data['logo']->isValid()) {
            $logoPath = $data['logo']->store('logos','public');
        }
        return Merchant::create([
            'user_id'=>Auth::id(),
            'business_name'=>$data['business_name'],
            'logo_path'=>$logoPath,
            'bank_account_name'=>$data['bank_account_name'] ?? null,
            'bank_account_number'=>$data['bank_account_number'] ?? null,
            'bank_ifsc_swift'=>$data['bank_ifsc_swift'] ?? null,
            'payout_preferences'=>$data['payout_preferences'] ?? null,
        ]);
    }

    public function get(int $id): Merchant {
        return Merchant::with('user')->findOrFail($id);
    }

    public function updateForCurrentUser(int $id, array $data): Merchant {
        $merchant = Merchant::where('user_id', Auth::id())->findOrFail($id);
        if (isset($data['logo']) && $data['logo']->isValid()) {
            if ($merchant->logo_path) Storage::disk('public')->delete($merchant->logo_path);
            $data['logo_path'] = $data['logo']->store('logos','public');
        }
        unset($data['logo']);
        $merchant->update($data);
        return $merchant->refresh();
    }

    public function setApproval(int $id, string $status): Merchant {
        $merchant = Merchant::findOrFail($id);

        if ($status === 'approved') {
            $status = 'verified';
        }

        $merchant->status = $status;
        $merchant->save();
        return $merchant;
    }
}
