<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ReportDashboardMinimal extends Component
{
    public $reportType = 'sales';
    
    public function mount($reportType = 'sales')
    {
        $this->reportType = $reportType;
    }
    
    public function render()
    {
        return view('livewire.reports.report-dashboard-minimal');
    }
}