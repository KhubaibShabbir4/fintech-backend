<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model {
protected $fillable = [
'user_id','business_name','logo_path',
'bank_account_name','bank_account_number','bank_ifsc_swift',
'payout_preferences','status'
];
protected $casts = ['payout_preferences' => 'array'];

public function user() { return $this->belongsTo(User::class); }
public function payments() { return $this->hasMany(Payment::class); }
}
