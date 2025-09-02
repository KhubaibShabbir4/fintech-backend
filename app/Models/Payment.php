<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    protected $fillable = [
        'merchant_id','provider','provider_payment_id','currency',
        'amount','method','status','reference','metadata'
    ];
    protected $casts = ['metadata' => 'array'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function transaction() { return $this->hasOne(Transaction::class); }
    public function refunds() { return $this->hasMany(Refund::class); }
}
