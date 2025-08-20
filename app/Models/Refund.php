<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model {
    protected $fillable = ['payment_id','provider_refund_id','amount','status','reason'];

    public function payment() { return $this->belongsTo(Payment::class); }
}
