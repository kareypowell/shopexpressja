@extends('layouts.app')

@section('title', 'Financial Summary Report')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Breadcrumb Navigation -->
    <x-breadcrumb :items="[
        [
            'title' => 'Dashboard',
            'url' => route('admin.dashboard'),
            'icon' => '<path d=\"M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6\" stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\"/>'
        ],
        [
            'title' => 'Reports',
            'url' => route('reports.index')
        ],
        [
            'title' => 'Financial Summary',
            'url' => null
        ]
    ]" />

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{{ $title }}</h1>
        <p class="mt-2 text-gray-600">Comprehensive financial overview and performance metrics</p>
    </div>

    <!-- Report Dashboard Component -->
    @livewire('reports.report-dashboard', ['reportType' => $reportType])
</div>
@endsection