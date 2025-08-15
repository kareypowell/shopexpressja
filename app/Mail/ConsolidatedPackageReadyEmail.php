<?php

namespace App\Mail;

use App\Models\ConsolidatedPackage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConsolidatedPackageReadyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public ConsolidatedPackage $consolidatedPackage;
    public bool $showCosts;
    public ?string $specialInstructions;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param ConsolidatedPackage $consolidatedPackage
     * @param bool $showCosts
     * @param string|null $specialInstructions
     */
    public function __construct(User $user, ConsolidatedPackage $consolidatedPackage, bool $showCosts = true, ?string $specialInstructions = null)
    {
        $this->user = $user;
        $this->consolidatedPackage = $consolidatedPackage;
        $this->showCosts = $showCosts;
        $this->specialInstructions = $specialInstructions;
        
        // Ensure user profile is loaded
        $user->load('profile');
        
        // Ensure consolidated package has individual packages loaded
        $consolidatedPackage->load('packages');

        // Set queue configuration
        $this->onQueue('emails');
        $this->delay(now()->addSeconds(2)); // Small delay to ensure database transaction is committed
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Consolidated Package Ready for Pickup - ' . $this->consolidatedPackage->consolidated_tracking_number)
            ->view('emails.packages.consolidated-package-ready')
            ->with([
                'user' => $this->user,
                'consolidatedPackage' => $this->consolidatedPackage,
                'individualPackages' => $this->consolidatedPackage->packages,
                'showCosts' => $this->showCosts,
                'specialInstructions' => $this->specialInstructions,
            ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        // Log the failure
        \Log::error('Consolidated package ready email failed', [
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'user_email' => $this->user->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}