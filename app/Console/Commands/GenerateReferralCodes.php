<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Console\Command;

class GenerateReferralCodes extends Command
{
    protected $signature = 'referral:generate-codes';
    protected $description = 'Generate unique referral codes for existing customer users without one.';

    public function handle(ReferralService $service): int
    {
        $count = 0;
        $users = User::whereNull('referral_code')
            ->whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->get();

        foreach ($users as $user) {
            $service->ensureReferralCode($user);
            $count++;
            $this->line("[{$count}] {$user->phone} -> {$user->fresh()->referral_code}");
        }

        $this->info("Generated referral codes for {$count} customer(s).");
        return self::SUCCESS;
    }
}
