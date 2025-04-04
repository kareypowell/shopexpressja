<?php

namespace App\Http\Livewire\Manifests\Packages;

use Livewire\Component;
use App\Models\Shipper;
use App\Models\User;
use App\Models\Office;
use App\Models\Package;
use App\Models\PreAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Notifications\MissingPreAlertNotification;

class ManifestPackage extends Component
{
    public bool $isOpen = false;
    public int $user_id = 0;
    public $manifest_id = 0;
    public int $shipper_id = 0;
    public int $office_id = 0;
    public string $warehouse_receipt_no = '';
    public string $tracking_number = '';
    public string $description = '';
    public string $weight = '';
    public string $value = '';
    public string $status = '';
    public string $estimated_value = '';
    public $customerList = [];
    public $shipperList = [];
    public $officeList = [];


    public function mount()
    {
        $this->customerList = User::where('role_id', 3)
                                  ->where('email_verified_at', '!=', '')
                                  ->orderBy('last_name', 'asc')->get();

        $this->officeList = Office::orderBy('name', 'asc')->get();

        $this->shipperList = Shipper::orderBy('name', 'asc')->get();

        $this->manifest_id = request()->route('manifest_id');
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function openModal()
    {
        $this->isOpen = true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function closeModal()
    {
        $this->isOpen = false;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    private function resetInputFields()
    {
        $this->user_id = 0;
        $this->shipper_id = 0;
        $this->office_id = 0;
        $this->warehouse_receipt_no = '';
        $this->tracking_number = '';
        $this->description = '';
        $this->weight = '';
        $this->status = '';
        $this->estimated_value = '';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function store()
    {
        $this->validate([
            'user_id' => ['required', 'integer'],
            'shipper_id' => ['required', 'integer'],
            'office_id' => ['required', 'integer'],
            'tracking_number' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric'],
            'estimated_value' => ['required', 'numeric'],
        ]);

        $package = Package::create([
            'user_id' => $this->user_id,
            'shipper_id' => $this->shipper_id,
            'office_id' => $this->office_id,
            'warehouse_receipt_no' => $this->generateWarehouseReceiptNumber(),
            'tracking_number' => $this->tracking_number,
            'description' => $this->description,
            'weight' => $this->weight,
            'status' => $this->updatePackageStatus(),
            'estimated_value' => $this->estimated_value,
            'manifest_id' => $this->manifest_id,
        ]);

        if ($package) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Package Created Successfully.',
            ]);
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Package Creation Failed.',
            ]);
        }

        $this->closeModal();
        $this->resetInputFields();
    }

    /**
     * Generate a unique warehouse receipt number with 'SHS' prefix.
     * Starts at 0001 and increments by 1, ensuring no collision with existing numbers.
     *
     * @return string
     */
    private function generateWarehouseReceiptNumber(): string
    {
        // Find the highest existing receipt number
        $highestReceiptNumber = Package::where('warehouse_receipt_no', 'like', 'SHS%')
            ->get()
            ->map(function ($package) {
                // Extract the numeric part from the warehouse_receipt_no
                $numericPart = (int) substr($package->warehouse_receipt_no, 3);
                return $numericPart;
            })
            ->max();

        // Start with 1 or increment the highest by 1
        $nextNumber = $highestReceiptNumber ? $highestReceiptNumber + 1 : 1;

        // Format to ensure 4 digits with leading zeros
        $formattedNumber = sprintf('SHS%04d', $nextNumber);

        return $formattedNumber;
    }

    /**
     * Determine package status based on Pre-Alerts and notify customer if needed.
     * 
     * @return string The package status ('Processing' or 'Pending')
     */
    private function updatePackageStatus(): string
    {
        $preAlert = PreAlert::where('tracking_number', $this->tracking_number)->first();
        $status = $preAlert ? 'Processing' : 'Pending';

        // If no pre-alert exists, send notification email to the customer
        if (!$preAlert) {
            $this->sendMissingPreAlertNotification();
        }

        return $status;
    }

    /**
     * Send notification email to customer about missing pre-alert.
     *
     * @return void
     */
    private function sendMissingPreAlertNotification(): void
    {
        // Get the user/customer associated with this package
        $user = User::find($this->user_id);

        // Send the email notification
        $user->notify(new MissingPreAlertNotification($user, $this->tracking_number, $this->description));

        // Optionally log that the notification was sent
        Log::info("Missing pre-alert notification sent for tracking number: {$this->tracking_number}");
    }

    public function render()
    {
        return view('livewire.manifests.packages.manifest-package');
    }
}
