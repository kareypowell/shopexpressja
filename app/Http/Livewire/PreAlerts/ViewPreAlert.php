<?php

namespace App\Http\Livewire\PreAlerts;

use App\Models\PreAlert as PreAlertModel;
use App\Models\Shipper;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Package;
use App\Models\PackagePreAlert;
use App\Models\User;
use App\Notifications\ProcessingPreAlertNotification;
use Illuminate\Support\Facades\Log;

class ViewPreAlert extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;
    public int $shipper_id = 0;
    public string $tracking_number = '';
    public string $description = '';
    public $value = '';
    public $file_path = null;
    public string $file_url = '';
    public $shipperList = [];

    public $fromCurrency = 'USD';
    public $toCurrency = 'JMD';
    public $amount = 1;
    public $result = null;
    public $loading = true;
    public $error = null;

    public $pre_alert_id = null;

    public function mount()
    {
        $this->pre_alert_id = request()->route('pre_alert_id');

        $this->shipperList = Shipper::orderBy('name', 'asc')->get();

        $this->value = 0.0;

        $preAlert = PreAlertModel::find($this->pre_alert_id);
        if ($preAlert) {
            $this->shipper_id = $preAlert->shipper_id;
            $this->tracking_number = $preAlert->tracking_number;
            $this->description = $preAlert->description;
            $this->value = $preAlert->value;
            $this->file_url = $preAlert->file_path;
        }
    }

    public function update()
    {
        $this->validate([
            'shipper_id' => 'required|integer',
            'tracking_number' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'file_path' => 'nullable|file|max:5120', // 5MB Max
        ]);

        if ($this->file_path) {
            // Store the file in the public disk under the 'pre-alerts' directory
            $path = $this->file_path->store('pre-alerts', 'public');

            // Get the public URL
            $this->file_url = config('app.url') . Storage::url($path);
        }

        $preAlert = PreAlertModel::find($this->pre_alert_id);
        $preAlert->update([
            'shipper_id' => $this->shipper_id,
            // 'tracking_number' => $this->tracking_number,
            'description' => $this->description,
            'value' => $this->value,
            'file_path' => $this->file_url,
        ]);

        // We need to update the package status to "Processing" if it exists
        // and the tracking number matches the one in the pre-alert.
        $package = Package::where('tracking_number', $this->tracking_number)->first();
        if ($package) {
            $packagePreAlert = PackagePreAlert::where('package_id', $package->id)->first();
            if ($packagePreAlert) {
                $packagePreAlert->update([
                    'status' => 'processing',
                ]);

                // Update the package status to "Processing"
                $package->update([
                    'status' => 'processing',
                ]);

                // Optionally, you can also update the package's estimated value
                // to match the pre-alert value
                $package->update([
                    'estimated_value' => $this->value,
                ]);

                // Send notification email to customer about processing pre-alert
                $this->sendProcessingNotification($packagePreAlert);
            }
        }

        if ($preAlert) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Pre-Alert Updated Successfully.',
            ]);
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Pre-Alert Update Failed.',
            ]);
        }

        return redirect('/pre-alerts');
    }

    /**
     * Send notification email to customer about processing pre-alert.
     *
     * @return void
     */
    private function sendProcessingNotification($packagePreAlert): void
    {
        // Get the user/customer associated with this package
        $user = User::find($packagePreAlert->user_id);

        // Send the email notification
        $user->notify(new ProcessingPreAlertNotification($user, $this->tracking_number, $this->description));

        // Optionally log that the notification was sent
        Log::info("Processing pre-alert notification sent for tracking number: {$this->tracking_number}");
    }

    public function render()
    {
        return view('livewire.pre-alerts.view-pre-alert');
    }
}
