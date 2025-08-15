<div class="consolidation-toggle-container">
    <div class="flex items-center justify-between p-4 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-900">Package Consolidation</h3>
                <p class="text-xs text-gray-500">
                    @if($consolidationMode)
                        Group multiple packages together for easier management
                    @else
                        View packages individually
                    @endif
                </p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3">
            <!-- Status Indicator -->
            <div class="flex items-center space-x-2">
                <span class="text-xs font-medium {{ $consolidationMode ? 'text-green-600' : 'text-gray-500' }}">
                    {{ $consolidationMode ? 'ON' : 'OFF' }}
                </span>
                <div class="w-2 h-2 rounded-full {{ $consolidationMode ? 'bg-green-500' : 'bg-gray-300' }}"></div>
            </div>
            
            <!-- Toggle Switch -->
            <button 
                wire:click="toggleConsolidationMode" 
                type="button" 
                class="consolidation-toggle relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $consolidationMode ? 'bg-blue-600' : 'bg-gray-200' }}"
                role="switch" 
                aria-checked="{{ $consolidationMode ? 'true' : 'false' }}"
                aria-labelledby="consolidation-toggle-label"
            >
                <span class="sr-only">Toggle consolidation mode</span>
                <span 
                    aria-hidden="true" 
                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $consolidationMode ? 'translate-x-5' : 'translate-x-0' }}"
                ></span>
            </button>
        </div>
    </div>
    
    <!-- Mode Description -->
    @if($consolidationMode)
        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Consolidation mode is active.</strong> You can now select multiple packages to group them together. 
                        Consolidated packages will be managed as a single unit while preserving individual tracking details.
                    </p>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Flash Message -->
    <div id="consolidation-message" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-md hidden">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p id="consolidation-message-text" class="text-sm text-green-700"></p>
            </div>
        </div>
    </div>
</div>

<style>
.consolidation-toggle-container {
    transition: all 0.3s ease;
}

.consolidation-toggle:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.consolidation-toggle span {
    transition: transform 0.2s ease-in-out;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth animations for toggle interactions
    const toggleButtons = document.querySelectorAll('.consolidation-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Add a subtle animation feedback
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
    
    // Listen for consolidation mode changes
    window.addEventListener('consolidationModeChanged', function(event) {
        const isEnabled = event.detail;
        
        // Update any other UI elements that depend on consolidation mode
        document.body.classList.toggle('consolidation-mode-active', isEnabled);
        
        // Dispatch custom event for other components to listen to
        window.dispatchEvent(new CustomEvent('consolidation-mode-updated', {
            detail: { enabled: isEnabled }
        }));
    });
    
    // Listen for show-message browser events
    window.addEventListener('show-message', function(event) {
        const message = event.detail.message;
        const messageElement = document.getElementById('consolidation-message');
        const messageText = document.getElementById('consolidation-message-text');
        
        if (messageElement && messageText) {
            messageText.textContent = message;
            messageElement.classList.remove('hidden');
            
            // Add fade-in animation
            messageElement.style.opacity = '0';
            messageElement.style.transform = 'translateY(-10px)';
            
            requestAnimationFrame(() => {
                messageElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                messageElement.style.opacity = '1';
                messageElement.style.transform = 'translateY(0)';
            });
            
            // Auto-hide after 3 seconds with fade-out
            setTimeout(() => {
                messageElement.style.opacity = '0';
                messageElement.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    messageElement.classList.add('hidden');
                    messageElement.style.transition = '';
                }, 300);
            }, 3000);
        }
    });
});
</script>