<div>
    <!-- History Modal -->
    @if($showModal && $consolidatedPackage)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history mr-2"></i>
                        Consolidation History - {{ $consolidatedPackage->consolidated_tracking_number }}
                    </h5>
                    <button type="button" class="close" wire:click="closeModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Package Summary -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Package Information</h6>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">Tracking Number:</dt>
                                        <dd class="col-sm-7">{{ $consolidatedPackage->consolidated_tracking_number }}</dd>
                                        
                                        <dt class="col-sm-5">Customer:</dt>
                                        <dd class="col-sm-7">{{ $consolidatedPackage->customer->name ?? 'Unknown' }}</dd>
                                        
                                        <dt class="col-sm-5">Status:</dt>
                                        <dd class="col-sm-7">
                                            <span class="badge badge-{{ $consolidatedPackage->status === 'delivered' ? 'success' : ($consolidatedPackage->status === 'ready' ? 'info' : 'warning') }}">
                                                {{ ucfirst($consolidatedPackage->status) }}
                                            </span>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Package Count:</dt>
                                        <dd class="col-sm-7">{{ $consolidatedPackage->packages->count() }} packages</dd>
                                        
                                        <dt class="col-sm-5">Total Weight:</dt>
                                        <dd class="col-sm-7">{{ number_format($consolidatedPackage->total_weight, 2) }} lbs</dd>
                                        
                                        <dt class="col-sm-5">Total Cost:</dt>
                                        <dd class="col-sm-7">${{ number_format($consolidatedPackage->total_cost, 2) }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">History Summary</h6>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-6">Total Actions:</dt>
                                        <dd class="col-sm-6">{{ $historySummary['total_actions'] ?? 0 }}</dd>
                                        
                                        @if(isset($historySummary['actions_by_type']))
                                            @foreach($historySummary['actions_by_type'] as $action => $count)
                                                <dt class="col-sm-6">{{ ucfirst(str_replace('_', ' ', $action)) }}:</dt>
                                                <dd class="col-sm-6">{{ $count }}</dd>
                                            @endforeach
                                        @endif
                                        
                                        <dt class="col-sm-6">Unique Users:</dt>
                                        <dd class="col-sm-6">{{ $historySummary['unique_users'] ?? 0 }}</dd>
                                        
                                        @if(isset($historySummary['first_action']))
                                            <dt class="col-sm-6">First Action:</dt>
                                            <dd class="col-sm-6">{{ $historySummary['first_action']->performed_at->format('M j, Y H:i') }}</dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters and Export -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-4">
                                    <select wire:model="filterAction" class="form-control form-control-sm">
                                        @foreach($availableActions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select wire:model="filterDays" class="form-control form-control-sm">
                                        @foreach($availableDays as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times mr-1"></i>Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <button wire:click="showExportModal" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download mr-1"></i>Export Audit Trail
                            </button>
                        </div>
                    </div>

                    <!-- History Table -->
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Performed At</th>
                                    <th>Performed By</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $record)
                                    <tr>
                                        <td>
                                            <span class="badge badge-{{ $record->action === 'consolidated' ? 'success' : ($record->action === 'unconsolidated' ? 'warning' : 'info') }}">
                                                {{ $record->action_description }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $record->performed_at->format('M j, Y H:i:s') }}</small>
                                            <br>
                                            <small class="text-muted">{{ $record->performed_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @if($record->performedBy)
                                                <strong>{{ $record->performedBy->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $record->performedBy->email }}</small>
                                            @else
                                                <span class="text-muted">Unknown User</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->formatted_details)
                                                <div class="small">
                                                    @foreach($record->formatted_details as $key => $value)
                                                        <div><strong>{{ $key }}:</strong> {{ $value }}</div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">No additional details</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-history fa-2x mb-2"></i>
                                            <br>
                                            No history records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($history->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $history->links() }}
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Export Modal -->
    @if($showExportModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-download mr-2"></i>
                        Export Audit Trail
                    </h5>
                    <button type="button" class="close" wire:click="closeExportModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="exportFormat">Export Format</label>
                        <select wire:model="exportFormat" id="exportFormat" class="form-control">
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                    
                    @error('export')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        The export will include all consolidation history for this package, including package details and user information.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeExportModal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="exportAuditTrail">
                        <i class="fas fa-download mr-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>