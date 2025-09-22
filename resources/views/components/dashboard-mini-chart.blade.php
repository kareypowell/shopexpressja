@props([
    'title',
    'chartId',
    'chartData' => [],
    'chartType' => 'line',
    'height' => '200px',
    'url' => null
])

<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $title }}</h3>
            @if($url)
                <a href="{{ $url }}" class="text-sm text-blue-600 hover:text-blue-900">
                    View details →
                </a>
            @endif
        </div>
        
        <div class="relative" style="height: {{ $height }};">
            <canvas id="{{ $chartId }}" class="w-full h-full"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('{{ $chartId }}');
    if (ctx) {
        const chartData = @json($chartData);
        
        new Chart(ctx, {
            type: '{{ $chartType }}',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                elements: {
                    line: {
                        tension: 0.4
                    },
                    point: {
                        radius: 3,
                        hoverRadius: 6
                    }
                }
            }
        });
    }
});
</script>
@endpush