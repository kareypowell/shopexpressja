@extends('layouts.app')

@section('title', 'Sales & Collections Report')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Sales & Collections Report</h1>
        @livewire('reports.report-dashboard-fixed', ['reportType' => 'sales'])
    </div>
</div>
@endsection