<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralPayout extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'admin_notes',
        'payment_method',
        'payment_details',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
