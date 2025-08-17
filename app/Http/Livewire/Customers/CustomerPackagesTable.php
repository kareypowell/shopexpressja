<?php

namespace App\Http\Livewire\Customers;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerPackagesTable extends DataTableComponent
{
    use AuthorizesRequests;
    
    public User $customer;
    public $showCostBreakdown = false;
    
    protected $model = Package::class;

    public function mount(User $customer)
    {
        $this->customer = $customer;
        $this->authorize('customer.view', $customer);
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('created_at', 'desc')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPerPage(25)
            ->setTableRowUrl(function($row) {
                return route('admin.packages.show', $row);
            })
            ->setTableRowUrlTarget(function($row) {
                return '_blank';
            });
    }

    public array $filterNames = [
        'status' => 'Status',
        'date_range' => 'Date Range',
        'cost_range' => 'Cost Range',
    ];

    public function filters(): array
    {
        return [
            'status' => Filter::make('Status')
                ->select([
                    '' => 'Any',
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delayed' => 'Delayed',
                    'ready' => 'Ready for Pickup',
                    'delivered' => 'Delivered',
                ]),
            'date_range' => Filter::make('Date Range')
                ->select([
                    '' => 'Any',
                    'last_30_days' => 'Last 30 Days',
                    'last_90_days' => 'Last 90 Days',
                    'last_year' => 'Last Year',
                ]),
            'cost_range' => Filter::make('Cost Range')
                ->select([
                    '' => 'Any',
                    '0-100' => '$0 - $100',
                    '100-500' => '$100 - $500',
                    '500-1000' => '$500 - $1,000',
                    '1000+' => '$1,000+',
                ]),
        ];
    }

    public function toggleCostBreakdown()
    {
        $this->showCostBreakdown = !$this->showCostBreakdown;
    }

    public function columns(): array
    {
        $columns = [
            Column::make("Tracking No.", "tracking_number")
                ->sortable()
                ->searchable(),
            Column::make("Description", "description")
                ->searchable(),
            Column::make("Date", "created_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->format('M d, Y') : '-'),
            Column::make("Weight (lbs)/Volume", "weight")
                ->sortable()
                ->format(fn($value) => $value ? number_format($value, 2) : '-'),
            Column::make("Status", "status")
                ->sortable()
                ->searchable(),
        ];

        // Only show Total Cost column if user should see costs
        if ($this->shouldShowCosts()) {
            $columns[] = Column::make("Total Cost", "")
                ->sortable(fn($query, $direction) => $query->orderByRaw("(COALESCE(freight_price, 0) + COALESCE(customs_duty, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)) {$direction}"))
                ->format(fn($value, $row) => '$' . number_format($this->calculateTotalCost($row), 2));
        }

        if ($this->showCostBreakdown && $this->shouldShowCosts()) {
            $columns[] = Column::make("Freight", "freight_price")
                ->sortable()
                ->format(fn($value) => '$' . number_format($value ?? 0, 2));
            $columns[] = Column::make("Customs", "customs_duty")
                ->sortable()
                ->format(fn($value) => '$' . number_format($value ?? 0, 2));
            $columns[] = Column::make("Storage", "storage_fee")
                ->sortable()
                ->format(fn($value) => '$' . number_format($value ?? 0, 2));
            $columns[] = Column::make("Delivery", "delivery_fee")
                ->sortable()
                ->format(fn($value) => '$' . number_format($value ?? 0, 2));
        }

        $columns[] = Column::make("Details", "");

        return $columns;
    }

    protected function calculateTotalCost($package): float
    {
        return ($package->freight_price ?? 0) + 
               ($package->customs_duty ?? 0) + 
               ($package->storage_fee ?? 0) + 
               ($package->delivery_fee ?? 0);
    }

    /**
     * Determine if costs should be shown based on user role and viewing context
     */
    public function shouldShowCosts(): bool
    {
        $currentUser = auth()->user();
        
        // Admins and superadmins can always see costs
        if ($currentUser->role_id == 1 || $currentUser->role_id == 2) { // superadmin = 1, admin = 2
            return true;
        }
        
        // For customers, they can only see costs for their own packages
        // This method determines column visibility, individual row visibility is handled in the template
        return $currentUser->role_id == 3 && $currentUser->id === $this->customer->id; // customer = 3
    }

    /**
     * Determine if cost should be shown for a specific package based on its status
     */
    public function shouldShowCostForPackage($package): bool
    {
        // Handle null package
        if (!$package) {
            return false;
        }
        
        $currentUser = auth()->user();
        
        // Admins and superadmins can always see costs
        if ($currentUser->role_id == 1 || $currentUser->role_id == 2) { // superadmin = 1, admin = 2
            return true;
        }
        
        // For customers, only show costs when package is ready for pickup or delivered
        if ($currentUser->role_id == 3 && $currentUser->id === $this->customer->id) { // customer = 3
            return in_array($package->status->value, ['ready', 'ready_for_pickup', 'delivered']);
        }
        
        return false;
    }


    public function query(): Builder
    {
        return Package::query()
            ->orderBy('id', 'desc')
            ->with(['manifest', 'items', 'shipper', 'office', 'consolidatedPackage'])
            ->where('user_id', $this->customer->id)
            ->whereNull('consolidated_package_id') // Exclude packages that are part of consolidated packages
            ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
            ->when($this->getFilter('status'), fn($query, $status) => $query->where('status', $status))
            ->when($this->getFilter('date_range'), function($query, $range) {
                switch($range) {
                    case 'last_30_days':
                        return $query->where('created_at', '>=', now()->subDays(30));
                    case 'last_90_days':
                        return $query->where('created_at', '>=', now()->subDays(90));
                    case 'last_year':
                        return $query->where('created_at', '>=', now()->subYear());
                }
            })
            ->when($this->getFilter('cost_range'), function($query, $range) {
                switch($range) {
                    case '0-100':
                        return $query->havingRaw('(COALESCE(freight_price, 0) + COALESCE(customs_duty, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)) BETWEEN 0 AND 100');
                    case '100-500':
                        return $query->havingRaw('(COALESCE(freight_price, 0) + COALESCE(customs_duty, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)) BETWEEN 100 AND 500');
                    case '500-1000':
                        return $query->havingRaw('(COALESCE(freight_price, 0) + COALESCE(customs_duty, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)) BETWEEN 500 AND 1000');
                    case '1000+':
                        return $query->havingRaw('(COALESCE(freight_price, 0) + COALESCE(customs_duty, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)) > 1000');
                }
            });
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.customer-packages-table';
    }









    public function getPackageStats(): array
    {
        // Get ALL individual packages (including those in consolidated packages)
        // This gives the true count of packages the customer has
        $allPackages = $this->customer->packages;
        
        // Calculate costs only for packages where costs should be visible
        $totalSpent = 0;
        $packagesWithVisibleCosts = 0;
        
        foreach ($allPackages as $package) {
            if ($this->shouldShowCostForPackage($package)) {
                $totalSpent += $this->calculateTotalCost($package);
                $packagesWithVisibleCosts++;
            }
        }
        
        return [
            'total_packages' => $allPackages->count(), // Count all individual packages
            'total_spent' => $totalSpent,
            'average_cost' => $packagesWithVisibleCosts > 0 ? $totalSpent / $packagesWithVisibleCosts : 0,
            'status_breakdown' => $allPackages->filter(fn($package) => $package->status !== null)->groupBy(fn($package) => $package->status->value)->map->count(),
        ];
    }
}
