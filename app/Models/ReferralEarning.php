<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralEarning extends Model
{
    protected $fillable = [
        'user_id',
        'referral_id',
        'payment_id',
        'amount',
        'percent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referral()
    {
        return $this->belongsTo(User::class, 'referral_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
