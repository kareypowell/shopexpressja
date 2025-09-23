<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Collections Chart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @livewireStyles
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-8">Collections Chart Test</h1>
        
        <div class="bg-white rounded-lg shadow p-6">
            @livewire('reports.collections-chart', ['filters' => [
                'date_range' => '30_days',
                'manifest_type' => 'all'
            ]])
        </div>
    </div>

    @livewireScripts
</body>
</html>