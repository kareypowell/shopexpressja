@extends('layouts.app')

@section('title', 'Sales & Collections Report')

@section('content')
    @livewire('reports.report-dashboard', ['type' => 'sales'])
@endsection