<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserSignupNotification;
use App\Models\User;
use Carbon\Carbon;

class WeeklyUserSignupReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shs:user-signup-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a weekly report of new user signups via email.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $newUsers = User::whereBetween('created_at', [
            now()->startOfWeek(Carbon::SUNDAY),
            now()->endOfWeek(Carbon::SATURDAY)
        ])->get();

        $newUserCount = $newUsers->count();

        if ($newUserCount > 0) {
            Mail::to('support@shopexpressja.com')
                ->send((new UserSignupNotification($newUsers, $newUserCount))
                    ->from('noreply@shopexpressja.com', 'Ship Heaven Shark Ltd.'));

            $this->info("Weekly user signup report sent successfully. Total new users: {$newUserCount}");
        } else {
            $this->info("No new user signups in the current week.");
        }

        return 0;
    }
}
