@extends('layouts.app')

@section('title', 'Manifest Performance Report')

@section('content')
    @livewire('reports.report-dashboard', ['type' => 'manifests'])
@endsection