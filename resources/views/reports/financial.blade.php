@extends('layouts.app')

@section('title', 'Financial Summary Report')

@section('content')
    @livewire('reports.report-dashboard', ['type' => 'financial'])
@endsection