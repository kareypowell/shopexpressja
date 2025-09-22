@props([
    'title',
    'description' => '',
    'icon' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'neutral', // positive, negative, neutral
    'url' => null,
    'loading' => false
])

<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                @if($icon)
                    <div class="w-8 h-8 bg-gray-100 rounded-md flex items-center justify-center">
                        {!! $icon !!}
                    </div>
                @endif
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">{{ $title }}</dt>
                    <dd class="flex items-baseline">
                        @if($loading)
                            <div class="animate-pulse">
                                <div class="h-6 bg-gray-200 rounded w-20"></div>
                            </div>
                        @else
                            <div class="text-2xl font-semibold text-gray-900">{{ $value }}</div>
                            @if($change !== null)
                                <div class="ml-2 flex items-baseline text-sm font-semibold 
                                    @if($changeType === 'positive') text-green-600
                                    @elseif($changeType === 'negative') text-red-600
                                    @else text-gray-500 @endif">
                                    @if($changeType === 'positive')
                                        <svg class="self-center flex-shrink-0 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @elseif($changeType === 'negative')
                                        <svg class="self-center flex-shrink-0 h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                    <span class="sr-only">{{ $changeType === 'positive' ? 'Increased' : ($changeType === 'negative' ? 'Decreased' : 'Changed') }} by</span>
                                    {{ $change }}
                                </div>
                            @endif
                        @endif
                    </dd>
                    @if($description)
                        <dd class="text-sm text-gray-500 mt-1">{{ $description }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    @if($url)
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <a href="{{ $url }}" class="font-medium text-blue-700 hover:text-blue-900 flex items-center">
                    View detailed report
                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    @endif
</div>