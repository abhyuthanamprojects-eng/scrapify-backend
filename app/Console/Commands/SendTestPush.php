<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestPushNotification;
use Illuminate\Console\Command;

class SendTestPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:test-push {user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test push notification to a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return;
        }

        if (!$user->fcm_token) {
            $this->error("User {$user->name} does not have an fcm_token.");
            return;
        }

        $this->info("Sending test push notification to {$user->name}...");

        try {
            $user->notify(new TestPushNotification());
            $this->info("Notification sent successfully!");
        } catch (\Exception $e) {
            $this->error("Failed to send notification: " . $e->getMessage());
        }
    }
}
