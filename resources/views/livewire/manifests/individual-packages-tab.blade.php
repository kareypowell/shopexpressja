<div class="individual-packages-tab">
    <div class="bg-base-100 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Individual Packages</h3>
        <div class="text-base-content/70">
            <p>This tab will contain all individual packages functionality.</p>
            <p class="mt-2">Implementation will be completed in task 6.</p>
            <p class="mt-2">Manifest ID: {{ $manifest->id }}</p>
            <p>Individual Packages Count: {{ $manifest->packages()->whereNull('consolidated_package_id')->count() }}</p>
        </div>
    </div>
</div>