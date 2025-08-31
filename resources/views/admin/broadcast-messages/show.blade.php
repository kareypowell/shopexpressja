@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Broadcast Message Details</h1>
        <a href="{{ route('admin.broadcast-messages.index') }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Back to Messages
        </a>
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Message Information</h3>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Subject</dt>
                            <dd class="text-sm text-gray-900">{{ $broadcastMessage->subject }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($broadcastMessage->status === 'sent') bg-green-100 text-green-800
                                    @elseif($broadcastMessage->status === 'scheduled') bg-yellow-100 text-yellow-800
                                    @elseif($broadcastMessage->status === 'draft') bg-gray-100 text-gray-800
                                    @elseif($broadcastMessage->status === 'failed') bg-red-100 text-red-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ ucfirst($broadcastMessage->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Recipient Type</dt>
                            <dd class="text-sm text-gray-900">{{ ucfirst($broadcastMessage->recipient_type) }} Customers</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Recipient Count</dt>
                            <dd class="text-sm text-gray-900">{{ number_format($broadcastMessage->recipient_count) }}</dd>
                        </div>
                    </dl>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Timing Information</h3>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="text-sm text-gray-900">{{ $broadcastMessage->created_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        @if($broadcastMessage->scheduled_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Scheduled For</dt>
                            <dd class="text-sm text-gray-900">{{ $broadcastMessage->scheduled_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        @endif
                        @if($broadcastMessage->sent_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sent At</dt>
                            <dd class="text-sm text-gray-900">{{ $broadcastMessage->sent_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sender</dt>
                            <dd class="text-sm text-gray-900">{{ $broadcastMessage->sender->name ?? 'Unknown' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Message Content</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="prose max-w-none">
                        {!! $broadcastMessage->content !!}
                    </div>
                </div>
            </div>

            @if($broadcastMessage->deliveries->count() > 0)
            <div class="border-t pt-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Delivery Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ $broadcastMessage->deliveries->where('status', 'sent')->count() }}</div>
                        <div class="text-sm text-green-600">Delivered</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600">{{ $broadcastMessage->deliveries->where('status', 'pending')->count() }}</div>
                        <div class="text-sm text-yellow-600">Pending</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-red-600">{{ $broadcastMessage->deliveries->where('status', 'failed')->count() }}</div>
                        <div class="text-sm text-red-600">Failed</div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-gray-600">{{ $broadcastMessage->deliveries->where('status', 'bounced')->count() }}</div>
                        <div class="text-sm text-gray-600">Bounced</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection