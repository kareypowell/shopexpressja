@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="container mx-auto px-4 py-6">
    @livewire('admin.audit-log-management')
</div>
@endsection