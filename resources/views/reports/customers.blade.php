@extends('layouts.app')

@section('title', 'Customer Analytics Report')

@section('content')
    @livewire('reports.report-dashboard', ['type' => 'customers'])
@endsection