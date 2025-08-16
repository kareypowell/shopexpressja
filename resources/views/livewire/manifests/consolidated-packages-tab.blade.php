<div class="consolidated-packages-tab">
    <div class="bg-base-100 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Consolidated Packages</h3>
        <div class="text-base-content/70">
            <p>This tab will contain all consolidated packages functionality.</p>
            <p class="mt-2">Implementation will be completed in task 5.</p>
            <p class="mt-2">Manifest ID: {{ $manifest->id }}</p>
            <p>Consolidated Packages Count: {{ $manifest->packages()->whereNotNull('consolidated_package_id')->distinct('consolidated_package_id')->count('consolidated_package_id') }}</p>
        </div>
    </div>
</div>