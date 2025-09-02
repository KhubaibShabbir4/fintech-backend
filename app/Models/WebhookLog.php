<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model {
    protected $fillable = ['provider','event_id','type','processed','payload','received_at'];
    protected $casts = ['payload'=>'array','processed'=>'boolean','received_at'=>'datetime'];
}
