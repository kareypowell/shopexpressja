@extends('layouts.app')

@section('title', 'Manifest Performance Report')

@section('content')
<!-- Report Dashboard Component -->
@livewire('reports.report-dashboard', ['reportType' => $reportType, 'reportTitle' => $title])
@endsection