<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MockSmsService
{
    public function sendVerificationCode(string $phone, string $code): bool
    {
        // Simulate sending SMS
        Log::info("SMS sent to {$phone}: {$code}");
        return true;
    }
}
