@extends('layouts.app')

@section('title', 'Sales & Collections Report')

@section('content')
<!-- Report Dashboard Component -->
@livewire('reports.report-dashboard', ['reportType' => $reportType, 'reportTitle' => $title])
@endsection