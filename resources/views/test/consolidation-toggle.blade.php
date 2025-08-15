@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Consolidation Toggle Test</h1>
        
        @livewire('consolidation-toggle')
        
        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Test Instructions</h2>
            <ul class="text-sm text-gray-600 space-y-1">
                <li>• Click the toggle switch to enable/disable consolidation mode</li>
                <li>• Observe the visual feedback and status changes</li>
                <li>• Check that messages appear and auto-hide after 3 seconds</li>
                <li>• Verify accessibility attributes are present</li>
            </ul>
        </div>
    </div>
</div>
@endsection