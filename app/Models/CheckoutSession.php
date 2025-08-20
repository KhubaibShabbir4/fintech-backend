<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutSession extends Model {
    protected $fillable = [
        'merchant_id','session_ref','amount','currency','method',
        'return_url_success','return_url_failure','status','customer','cart'
    ];
    protected $casts = ['customer'=>'array','cart'=>'array'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
}
