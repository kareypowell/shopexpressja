@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Compose Message</h1>
        <a href="{{ route('admin.broadcast-messages.index') }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Back to Messages
        </a>
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="p-6">
            @livewire('admin.broadcast-composer')
        </div>
    </div>
</div>
@endsection