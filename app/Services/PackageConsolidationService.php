<?php

namespace App\Services;

use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Models\User;
use App\Enums\PackageStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class PackageConsolidationService
{
    /**
     * Consolidate multiple packages into a single consolidated package
     *
     * @param array $packageIds Array of package IDs to consolidate
     * @param User $admin Admin user performing the consolidation
     * @param array $options Additional options: notes, etc.
     * @return array Result with success status and consolidated package or error message
     */
    public function consolidatePackages(array $packageIds, User $admin, array $options = []): array
    {
        try {
            DB::beginTransaction();

            // Validate input
            if (empty($packageIds) || count($packageIds) < 2) {
                throw new Exception('At least 2 packages are required for consolidation');
            }

            // Validate consolidation eligibility
            $validationResult = $this->validateConsolidation($packageIds);
            if (!$validationResult['valid']) {
                throw new Exception($validationResult['message']);
            }

            // Get packages for consolidation
            $packages = Package::whereIn('id', $packageIds)
                ->availableForConsolidation()
                ->get();

            if ($packages->count() !== count($packageIds)) {
                throw new Exception('Some packages are not available for consolidation');
            }

            // Ensure all packages belong to the same customer
            $customerIds = $packages->pluck('user_id')->unique();
            if ($customerIds->count() > 1) {
                throw new Exception('All packages must belong to the same customer');
            }

            $customerId = $customerIds->first();

            // Calculate consolidated totals
            $totals = $this->calculateConsolidatedTotals($packages);

            // Generate consolidated tracking number
            $consolidatedTrackingNumber = $this->generateConsolidatedTrackingNumber();

            // Create consolidated package
            $consolidatedPackage = ConsolidatedPackage::create([
                'consolidated_tracking_number' => $consolidatedTrackingNumber,
                'customer_id' => $customerId,
                'created_by' => $admin->id,
                'total_weight' => $totals['weight'],
                'total_quantity' => $totals['quantity'],
                'total_freight_price' => $totals['freight_price'],
                'total_customs_duty' => $totals['customs_duty'],
                'total_storage_fee' => $totals['storage_fee'],
                'total_delivery_fee' => $totals['delivery_fee'],
                'status' => $this->determineConsolidatedStatus($packages),
                'consolidated_at' => now(),
                'is_active' => true,
                'notes' => $options['notes'] ?? null,
            ]);

            // Update individual packages
            foreach ($packages as $package) {
                $package->update([
                    'consolidated_package_id' => $consolidatedPackage->id,
                    'is_consolidated' => true,
                    'consolidated_at' => now(),
                ]);
            }

            // Log consolidation action
            $this->logConsolidationAction('consolidated', $consolidatedPackage, $admin, [
                'package_ids' => $packageIds,
                'package_count' => $packages->count(),
                'total_weight' => $totals['weight'],
                'total_cost' => $totals['total_cost'],
            ]);

            DB::commit();

            Log::info('Packages consolidated successfully', [
                'consolidated_package_id' => $consolidatedPackage->id,
                'tracking_number' => $consolidatedTrackingNumber,
                'package_ids' => $packageIds,
                'customer_id' => $customerId,
                'admin_id' => $admin->id,
            ]);

            return [
                'success' => true,
                'consolidated_package' => $consolidatedPackage->load(['packages', 'customer', 'createdBy']),
                'message' => 'Packages consolidated successfully',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Package consolidation failed', [
                'error' => $e->getMessage(),
                'package_ids' => $packageIds,
                'admin_id' => $admin->id,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Unconsolidate packages by separating them back to individual packages
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param User $admin Admin user performing the unconsolidation
     * @param array $options Additional options: notes, etc.
     * @return array Result with success status and restored packages or error message
     */
    public function unconsolidatePackages(ConsolidatedPackage $consolidatedPackage, User $admin, array $options = []): array
    {
        try {
            DB::beginTransaction();

            // Validate unconsolidation eligibility
            if (!$consolidatedPackage->canBeUnconsolidated()) {
                throw new Exception('Consolidated package cannot be unconsolidated at this time');
            }

            // Get individual packages
            $packages = $consolidatedPackage->packages;
            
            if ($packages->isEmpty()) {
                throw new Exception('No packages found in consolidated package');
            }

            // Store package IDs for logging
            $packageIds = $packages->pluck('id')->toArray();

            // Restore individual packages
            foreach ($packages as $package) {
                $package->update([
                    'consolidated_package_id' => null,
                    'is_consolidated' => false,
                    'consolidated_at' => null,
                ]);
            }

            // Mark consolidated package as inactive
            $consolidatedPackage->update([
                'is_active' => false,
                'unconsolidated_at' => now(),
                'notes' => ($consolidatedPackage->notes ? $consolidatedPackage->notes . "\n\n" : '') . 
                          "Unconsolidated on " . now()->format('Y-m-d H:i:s') . 
                          (isset($options['notes']) ? " - {$options['notes']}" : ''),
            ]);

            // Log unconsolidation action
            $this->logConsolidationAction('unconsolidated', $consolidatedPackage, $admin, [
                'package_ids' => $packageIds,
                'package_count' => $packages->count(),
                'reason' => isset($options['notes']) ? $options['notes'] : 'Manual unconsolidation',
            ]);

            DB::commit();

            Log::info('Packages unconsolidated successfully', [
                'consolidated_package_id' => $consolidatedPackage->id,
                'tracking_number' => $consolidatedPackage->consolidated_tracking_number,
                'package_ids' => $packageIds,
                'admin_id' => $admin->id,
            ]);

            return [
                'success' => true,
                'packages' => $packages->fresh(),
                'message' => 'Packages unconsolidated successfully',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Package unconsolidation failed', [
                'error' => $e->getMessage(),
                'consolidated_package_id' => $consolidatedPackage->id,
                'admin_id' => $admin->id,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate if packages can be consolidated
     *
     * @param array $packageIds
     * @return array Validation result with valid flag and message
     */
    public function validateConsolidation(array $packageIds): array
    {
        try {
            // Check minimum package count
            if (count($packageIds) < 2) {
                return [
                    'valid' => false,
                    'message' => 'At least 2 packages are required for consolidation'
                ];
            }

            // Get packages
            $packages = Package::whereIn('id', $packageIds)->get();

            if ($packages->count() !== count($packageIds)) {
                return [
                    'valid' => false,
                    'message' => 'Some packages were not found'
                ];
            }

            // Check if all packages belong to the same customer
            $customerIds = $packages->pluck('user_id')->unique();
            if ($customerIds->count() > 1) {
                return [
                    'valid' => false,
                    'message' => 'All packages must belong to the same customer'
                ];
            }

            // Check if any packages are already consolidated
            $consolidatedPackages = $packages->where('is_consolidated', true);
            if ($consolidatedPackages->isNotEmpty()) {
                return [
                    'valid' => false,
                    'message' => 'Some packages are already consolidated'
                ];
            }

            // Check if packages are in compatible statuses
            $incompatibleStatuses = $packages->filter(function ($package) {
                return !$package->canBeConsolidated();
            });

            if ($incompatibleStatuses->isNotEmpty()) {
                return [
                    'valid' => false,
                    'message' => 'Some packages are not in a status that allows consolidation'
                ];
            }

            return [
                'valid' => true,
                'message' => 'Packages can be consolidated'
            ];

        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate consolidated totals from individual packages
     *
     * @param Collection $packages
     * @return array Array of calculated totals
     */
    public function calculateConsolidatedTotals(Collection $packages): array
    {
        $totals = [
            'weight' => 0,
            'quantity' => $packages->count(),
            'freight_price' => 0,
            'customs_duty' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ];

        foreach ($packages as $package) {
            $totals['weight'] += $package->weight ?? 0;
            $totals['freight_price'] += $package->freight_price ?? 0;
            $totals['customs_duty'] += $package->customs_duty ?? 0;
            $totals['storage_fee'] += $package->storage_fee ?? 0;
            $totals['delivery_fee'] += $package->delivery_fee ?? 0;
        }

        // Calculate total cost
        $totals['total_cost'] = $totals['freight_price'] + 
                               $totals['customs_duty'] + 
                               $totals['storage_fee'] + 
                               $totals['delivery_fee'];

        return $totals;
    }

    /**
     * Generate a unique consolidated tracking number
     *
     * @return string Generated tracking number
     */
    public function generateConsolidatedTrackingNumber(): string
    {
        $date = now()->format('Ymd');
        
        // Get count of consolidated packages created today
        $todayCount = ConsolidatedPackage::whereDate('created_at', now()->toDateString())->count();
        $sequence = $todayCount + 1;
        
        // Generate tracking number with format: CONS-YYYYMMDD-XXXX
        $trackingNumber = sprintf('CONS-%s-%04d', $date, $sequence);
        
        // Ensure uniqueness (in case of race conditions)
        while (ConsolidatedPackage::where('consolidated_tracking_number', $trackingNumber)->exists()) {
            $sequence++;
            $trackingNumber = sprintf('CONS-%s-%04d', $date, $sequence);
        }
        
        return $trackingNumber;
    }

    /**
     * Determine the consolidated status based on individual package statuses
     *
     * @param Collection $packages
     * @return string Consolidated status
     */
    protected function determineConsolidatedStatus(Collection $packages): string
    {
        $packageStatuses = $packages->pluck('status')->map(function ($status) {
            return is_string($status) ? $status : (string) $status;
        })->unique();

        // If all packages have the same status, use that status
        if ($packageStatuses->count() === 1) {
            return $packageStatuses->first();
        }

        // Determine consolidated status based on priority
        $statusPriority = [
            PackageStatus::DELIVERED => 6,
            PackageStatus::READY => 5,
            PackageStatus::CUSTOMS => 4,
            PackageStatus::SHIPPED => 3,
            PackageStatus::PROCESSING => 2,
            PackageStatus::PENDING => 1,
            PackageStatus::DELAYED => 0, // Lowest priority - indicates issues
        ];

        $highestPriorityStatus = $packageStatuses
            ->sortByDesc(function ($status) use ($statusPriority) {
                return $statusPriority[$status] ?? 0;
            })
            ->first();

        return $highestPriorityStatus;
    }

    /**
     * Log consolidation actions for audit trail
     *
     * @param string $action Action performed (consolidated, unconsolidated, status_changed)
     * @param ConsolidatedPackage $consolidatedPackage
     * @param User $user User who performed the action
     * @param array $details Additional details to log
     * @return ConsolidationHistory Created history record
     */
    protected function logConsolidationAction(string $action, ConsolidatedPackage $consolidatedPackage, User $user, array $details = []): ConsolidationHistory
    {
        // Create history record in database
        $historyRecord = ConsolidationHistory::create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => $action,
            'performed_by' => $user->id,
            'details' => $details,
            'performed_at' => now(),
        ]);

        // Also log to Laravel log for system monitoring
        Log::info("Package consolidation action: {$action}", [
            'history_id' => $historyRecord->id,
            'action' => $action,
            'consolidated_package_id' => $consolidatedPackage->id,
            'tracking_number' => $consolidatedPackage->consolidated_tracking_number,
            'customer_id' => $consolidatedPackage->customer_id,
            'performed_by' => $user->id,
            'performed_at' => $historyRecord->performed_at,
            'details' => $details,
        ]);

        return $historyRecord;
    }

    /**
     * Update consolidated package status and sync to individual packages
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param string $newStatus
     * @param User $user User performing the status update
     * @param array $options Additional options
     * @return array Result with success status
     */
    public function updateConsolidatedStatus(ConsolidatedPackage $consolidatedPackage, string $newStatus, User $user, array $options = []): array
    {
        try {
            DB::beginTransaction();

            // Validate status
            $validStatuses = PackageStatus::values();
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status provided');
            }

            $oldStatus = $consolidatedPackage->status;

            // Update consolidated package status
            $consolidatedPackage->update(['status' => $newStatus]);

            // Sync status to all individual packages
            $consolidatedPackage->syncPackageStatuses($newStatus, $user);

            // Log status change
            $this->logConsolidationAction('status_changed', $consolidatedPackage, $user, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'package_count' => $consolidatedPackage->packages()->count(),
                'reason' => $options['reason'] ?? 'Status updated',
            ]);

            // Send notification to customer if status changed
            if ($oldStatus !== $newStatus) {
                try {
                    $customer = $consolidatedPackage->customer;
                    if ($customer) {
                        $statusEnum = PackageStatus::from($newStatus);
                        $customer->notify(new \App\Notifications\ConsolidatedPackageStatusNotification(
                            $customer,
                            $consolidatedPackage,
                            $statusEnum
                        ));

                        Log::info('Consolidated package status notification sent', [
                            'consolidated_package_id' => $consolidatedPackage->id,
                            'customer_id' => $customer->id,
                            'new_status' => $newStatus,
                        ]);
                    }
                } catch (Exception $notificationException) {
                    // Don't fail the entire operation if notification fails
                    Log::error('Failed to send consolidated package status notification', [
                        'consolidated_package_id' => $consolidatedPackage->id,
                        'new_status' => $newStatus,
                        'error' => $notificationException->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Consolidated package status updated', [
                'consolidated_package_id' => $consolidatedPackage->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Status updated successfully',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Consolidated package status update failed', [
                'error' => $e->getMessage(),
                'consolidated_package_id' => $consolidatedPackage->id,
                'new_status' => $newStatus,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get consolidation history for a consolidated package
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param array $options Options for filtering/sorting history
     * @return Collection History of consolidation actions
     */
    public function getConsolidationHistory(ConsolidatedPackage $consolidatedPackage, array $options = []): Collection
    {
        $query = $consolidatedPackage->history()
            ->with('performedBy')
            ->orderBy('performed_at', 'desc');

        // Apply filters if provided
        if (isset($options['action'])) {
            $query->byAction($options['action']);
        }

        if (isset($options['days'])) {
            $query->recent($options['days']);
        }

        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }

        return $query->get();
    }

    /**
     * Get consolidation history summary for a consolidated package
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @return array Summary of consolidation history
     */
    public function getConsolidationHistorySummary(ConsolidatedPackage $consolidatedPackage): array
    {
        $history = $consolidatedPackage->history()->with('performedBy')->get();
        
        $summary = [
            'total_actions' => $history->count(),
            'actions_by_type' => $history->groupBy('action')->map->count(),
            'first_action' => $history->sortBy('performed_at')->first(),
            'last_action' => $history->sortByDesc('performed_at')->first(),
            'unique_users' => $history->pluck('performed_by')->unique()->count(),
        ];

        return $summary;
    }

    /**
     * Get packages available for consolidation for a specific customer
     *
     * @param int $customerId
     * @return Collection
     */
    public function getAvailablePackagesForCustomer(int $customerId): Collection
    {
        return Package::where('user_id', $customerId)
            ->availableForConsolidation()
            ->get();
    }

    /**
     * Get active consolidated packages for a customer
     *
     * @param int $customerId
     * @return Collection
     */
    public function getActiveConsolidatedPackagesForCustomer(int $customerId): Collection
    {
        return ConsolidatedPackage::where('customer_id', $customerId)
            ->active()
            ->with(['packages', 'createdBy'])
            ->get();
    }

    /**
     * Export consolidation audit trail for a consolidated package
     *
     * @param ConsolidatedPackage $consolidatedPackage
     * @param string $format Format for export (csv, json, array)
     * @return array|string Exported audit trail data
     */
    public function exportConsolidationAuditTrail(ConsolidatedPackage $consolidatedPackage, string $format = 'array')
    {
        $history = $this->getConsolidationHistory($consolidatedPackage);
        
        $auditData = $history->map(function ($record) {
            return [
                'id' => $record->id,
                'action' => $record->action,
                'action_description' => $record->action_description,
                'performed_at' => $record->performed_at->format('Y-m-d H:i:s'),
                'performed_by_id' => $record->performed_by,
                'performed_by_name' => $record->performedBy ? $record->performedBy->name : 'Unknown',
                'performed_by_email' => $record->performedBy ? $record->performedBy->email : 'Unknown',
                'details' => $record->details,
                'formatted_details' => $record->formatted_details,
            ];
        });

        // Add consolidated package information
        $packageInfo = [
            'consolidated_package_id' => $consolidatedPackage->id,
            'consolidated_tracking_number' => $consolidatedPackage->consolidated_tracking_number,
            'customer_id' => $consolidatedPackage->customer_id,
            'customer_name' => $consolidatedPackage->customer ? $consolidatedPackage->customer->name : 'Unknown',
            'customer_email' => $consolidatedPackage->customer ? $consolidatedPackage->customer->email : 'Unknown',
            'created_at' => $consolidatedPackage->created_at->format('Y-m-d H:i:s'),
            'is_active' => $consolidatedPackage->is_active,
            'total_weight' => $consolidatedPackage->total_weight,
            'total_cost' => $consolidatedPackage->total_cost,
            'package_count' => $consolidatedPackage->packages()->count(),
            'individual_packages' => $consolidatedPackage->packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'tracking_number' => $package->tracking_number,
                    'description' => $package->description,
                    'weight' => $package->weight,
                    'status' => $package->status,
                ];
            }),
        ];

        $exportData = [
            'export_generated_at' => now()->format('Y-m-d H:i:s'),
            'consolidated_package' => $packageInfo,
            'audit_trail' => $auditData->toArray(),
        ];

        switch ($format) {
            case 'json':
                return json_encode($exportData, JSON_PRETTY_PRINT);
            
            case 'csv':
                return $this->convertAuditTrailToCsv($exportData);
            
            case 'array':
            default:
                return $exportData;
        }
    }

    /**
     * Convert audit trail data to CSV format
     *
     * @param array $exportData
     * @return string CSV formatted data
     */
    protected function convertAuditTrailToCsv(array $exportData): string
    {
        $csv = [];
        
        // Add header
        $csv[] = 'Export Generated At,' . $exportData['export_generated_at'];
        $csv[] = 'Consolidated Package ID,' . $exportData['consolidated_package']['consolidated_package_id'];
        $csv[] = 'Consolidated Tracking Number,' . $exportData['consolidated_package']['consolidated_tracking_number'];
        $csv[] = 'Customer Name,' . $exportData['consolidated_package']['customer_name'];
        $csv[] = 'Customer Email,' . $exportData['consolidated_package']['customer_email'];
        $csv[] = '';
        
        // Add audit trail header
        $csv[] = 'Action,Action Description,Performed At,Performed By,Performed By Email,Details';
        
        // Add audit trail data
        foreach ($exportData['audit_trail'] as $record) {
            $detailsString = is_array($record['details']) ? json_encode($record['details']) : $record['details'];
            $csv[] = implode(',', [
                '"' . $record['action'] . '"',
                '"' . $record['action_description'] . '"',
                '"' . $record['performed_at'] . '"',
                '"' . $record['performed_by_name'] . '"',
                '"' . $record['performed_by_email'] . '"',
                '"' . str_replace('"', '""', $detailsString) . '"',
            ]);
        }
        
        return implode("\n", $csv);
    }

    /**
     * Get consolidation statistics for reporting
     *
     * @param array $filters Optional filters (date_from, date_to, customer_id, etc.)
     * @return array Consolidation statistics
     */
    public function getConsolidationStatistics(array $filters = []): array
    {
        $query = ConsolidationHistory::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('performed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('performed_at', '<=', $filters['date_to']);
        }

        if (isset($filters['customer_id'])) {
            $query->whereHas('consolidatedPackage', function ($q) use ($filters) {
                $q->where('customer_id', $filters['customer_id']);
            });
        }

        if (isset($filters['action'])) {
            $query->byAction($filters['action']);
        }

        $history = $query->with(['consolidatedPackage', 'performedBy'])->get();

        return [
            'total_actions' => $history->count(),
            'actions_by_type' => $history->groupBy('action')->map->count(),
            'actions_by_date' => $history->groupBy(function ($item) {
                return $item->performed_at->format('Y-m-d');
            })->map->count(),
            'actions_by_user' => $history->groupBy('performed_by')->map->count(),
            'unique_consolidated_packages' => $history->pluck('consolidated_package_id')->unique()->count(),
            'unique_users' => $history->pluck('performed_by')->unique()->count(),
            'date_range' => [
                'from' => $history->min('performed_at'),
                'to' => $history->max('performed_at'),
            ],
        ];
    }
}