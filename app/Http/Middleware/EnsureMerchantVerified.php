<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMerchantVerified {
    public function handle(Request $request, Closure $next) {
        $user = auth()->user();
        if ($user && $user->hasRole('merchant')) {
            $merchant = $user->merchant;
            if (!$merchant || $merchant->status !== 'verified') {
                return response()->json(['message'=>'Merchant not verified'], 403);
            }
        }
        return $next($request);
    }
}
