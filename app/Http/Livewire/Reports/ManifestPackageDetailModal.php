<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Models\Manifest;
use App\Models\Package;
use App\Services\ReportExportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ManifestPackageDetailModal extends Component
{
    public bool $show = false;
    public ?int $manifestId = null;
    public array $manifestData = [];
    public array $packages = [];
    public bool $isLoading = false;
    public ?string $error = null;

    protected $listeners = [
        'showManifestDetails' => 'openModal',
        'closeModal' => 'closeModal'
    ];

    public function openModal(int $manifestId, array $manifestData = [])
    {
        $this->manifestId = $manifestId;
        $this->manifestData = $manifestData;
        $this->show = true;
        $this->error = null;
        $this->packages = [];
        
        $this->loadPackageDetails();
    }

    public function closeModal()
    {
        $this->show = false;
        $this->manifestId = null;
        $this->manifestData = [];
        $this->packages = [];
        $this->error = null;
        $this->isLoading = false;
    }

    public function loadPackageDetails()
    {
        if (!$this->manifestId) {
            $this->error = 'No manifest ID provided';
            return;
        }

        try {
            $this->isLoading = true;
            $this->error = null;

            // Load manifest details if not provided
            if (empty($this->manifestData)) {
                $manifest = Manifest::find($this->manifestId);
                if (!$manifest) {
                    throw new \Exception('Manifest not found');
                }
                
                $this->manifestData = [
                    'manifest_name' => $manifest->name,
                    'manifest_type' => $manifest->type,
                    'shipment_date' => $manifest->shipment_date,
                ];
            }

            // Load packages with detailed information
            $packages = Package::with(['user', 'office'])
                ->where('manifest_id', $this->manifestId)
                ->get();

            $this->packages = $packages->map(function ($package) {
                $totalCharges = ($package->freight_price ?? 0) + 
                              ($package->clearance_fee ?? 0) + 
                              ($package->storage_fee ?? 0) + 
                              ($package->delivery_fee ?? 0);

                return [
                    'id' => $package->id,
                    'tracking_number' => $package->tracking_number,
                    'description' => $package->description,
                    'weight' => $package->weight,
                    'dimensions' => $this->formatDimensions($package),
                    'customer_id' => $package->user_id,
                    'customer_name' => $package->user ? $package->user->name : 'Unknown Customer',
                    'customer_email' => $package->user ? $package->user->email : '',
                    'freight_price' => $package->freight_price ?? 0,
                    'clearance_fee' => $package->clearance_fee ?? 0,
                    'storage_fee' => $package->storage_fee ?? 0,
                    'delivery_fee' => $package->delivery_fee ?? 0,
                    'total_charges' => $totalCharges,
                    'status' => $package->status,
                    'payment_status' => $this->getPaymentStatus($package),
                    'office_name' => $package->office ? $package->office->name : 'N/A',
                    'created_at' => $package->created_at,
                    'updated_at' => $package->updated_at,
                ];
            })->toArray();

            // Calculate manifest totals if not provided
            if (!isset($this->manifestData['total_owed']) || !isset($this->manifestData['total_collected'])) {
                $this->calculateManifestTotals();
            }

            $this->isLoading = false;
        } catch (\Exception $e) {
            Log::error('Error loading package details: ' . $e->getMessage(), [
                'manifest_id' => $this->manifestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->error = 'Failed to load package details: ' . $e->getMessage();
            $this->isLoading = false;
        }
    }

    protected function formatDimensions($package): ?string
    {
        $dimensions = [];
        
        if ($package->length) $dimensions[] = $package->length . '"L';
        if ($package->width) $dimensions[] = $package->width . '"W';
        if ($package->height) $dimensions[] = $package->height . '"H';
        
        return !empty($dimensions) ? implode(' × ', $dimensions) : null;
    }

    protected function getPaymentStatus($package): string
    {
        // Check if package has been distributed (paid)
        $distributionItems = DB::table('package_distribution_items')
            ->where('package_id', $package->id)
            ->count();

        if ($distributionItems > 0) {
            return 'paid';
        }

        // Check if customer has sufficient balance
        if ($package->user && $package->user->account_balance >= 0) {
            return 'pending';
        }

        return 'outstanding';
    }

    protected function calculateManifestTotals()
    {
        $totalOwed = collect($this->packages)->sum('total_charges');
        
        // Calculate collected amount from distributions
        $packageIds = collect($this->packages)->pluck('id')->toArray();
        
        $totalCollected = DB::table('package_distributions as pd')
            ->join('package_distribution_items as pdi', 'pd.id', '=', 'pdi.distribution_id')
            ->whereIn('pdi.package_id', $packageIds)
            ->sum('pd.total_amount');

        $this->manifestData['total_owed'] = $totalOwed;
        $this->manifestData['total_collected'] = $totalCollected;
    }

    public function viewCustomerDetails(int $customerId)
    {
        $this->emit('showCustomerDetails', $customerId);
    }

    public function exportPackageDetails()
    {
        try {
            $exportService = app(ReportExportService::class);
            
            $exportData = [
                'manifest_info' => $this->manifestData,
                'packages' => $this->packages,
                'export_type' => 'manifest_package_details',
                'generated_at' => now()->toISOString(),
            ];

            $jobId = $exportService->queueExport('csv', $exportData, auth()->user());

            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'success',
                'message' => 'Package details export started. You will be notified when complete.'
            ]);

            $this->emit('exportStarted', $jobId);
        } catch (\Exception $e) {
            Log::error('Error exporting package details: ' . $e->getMessage());
            
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'Export failed: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.reports.manifest-package-detail-modal');
    }
}