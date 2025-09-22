@extends('layouts.app')

@section('title', 'Customer Analytics Report')

@section('content')
<!-- Report Dashboard Component -->
@livewire('reports.report-dashboard', ['reportType' => $reportType, 'reportTitle' => $title])
@endsection