<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {
    protected $fillable = [
        'payment_id','customer_name','customer_email','customer_phone',
        'country_code','amount','status','extra'
    ];
    protected $casts = ['extra'=>'array'];

    public function payment() { return $this->belongsTo(Payment::class); }
}
