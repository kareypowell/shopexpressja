<div class="manifest-tabs-container" 
     x-data="manifestTabs()" 
     x-init="init()"
     role="region"
     aria-label="Manifest package management interface">
    
    <!-- Skip Link for Screen Readers -->
    <a href="#tab-content" 
       class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 z-50 
              bg-blue-600 text-white px-4 py-2 rounded-br-lg font-medium
              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        Skip to tab content
    </a>
    
    <!-- Tab Navigation -->
    <div role="tablist" 
         class="flex bg-white border-b border-gray-200 shadow-sm rounded-t-lg 
                overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100
                sm:overflow-x-visible"
         aria-label="Manifest package views"
         x-ref="tablist">
        
        @foreach($tabs as $tabKey => $tabData)
            <button role="tab" 
                    id="tab-{{ $tabKey }}"
                    class="relative flex items-center gap-2 sm:gap-3 
                           px-4 py-3 sm:px-6 sm:py-4 
                           text-sm font-medium transition-all duration-200 ease-in-out
                           flex-shrink-0 min-w-[120px] sm:min-w-0 sm:flex-1
                           touch-manipulation
                           {{ $activeTab === $tabKey 
                              ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-500' 
                              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset focus:z-10
                           first:rounded-tl-lg last:rounded-tr-lg
                           disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:click="switchTab('{{ $tabKey }}')"
                    aria-label="{{ $tabData['aria_label'] }}"
                    aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                    aria-controls="tabpanel-{{ $tabKey }}"
                    tabindex="{{ $activeTab === $tabKey ? '0' : '-1' }}"
                    x-ref="tab-{{ $tabKey }}"
                    @keydown.arrow-right.prevent="nextTab()"
                    @keydown.arrow-left.prevent="prevTab()"
                    @keydown.home.prevent="firstTab()"
                    @keydown.end.prevent="lastTab()"
                    @keydown.space.prevent="$wire.switchTab('{{ $tabKey }}')"
                    @keydown.enter.prevent="$wire.switchTab('{{ $tabKey }}')"
                    @focus="scrollTabIntoView($el)"
                    wire:loading.attr="disabled">
                
                <!-- Tab Icon -->
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0
                           {{ $activeTab === $tabKey ? 'text-blue-600' : 'text-gray-400' }}" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     aria-hidden="true">
                    @if($tabData['icon'] === 'archive-box')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    @elseif($tabData['icon'] === 'cube')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    @endif
                </svg>
                
                <!-- Tab Label -->
                <span class="hidden sm:inline font-semibold truncate">{{ $tabData['name'] }}</span>
                <span class="sm:hidden font-semibold truncate" 
                      title="{{ $tabData['name'] }}">
                    {{ explode(' ', $tabData['name'])[0] }}
                </span>
                
                <!-- Package Count Badge -->
                @if($tabData['count'] > 0)
                    <span class="inline-flex items-center px-2 py-0.5 sm:px-2.5 
                                 rounded-full text-xs font-medium flex-shrink-0
                                 {{ $activeTab === $tabKey 
                                    ? 'bg-blue-100 text-blue-800' 
                                    : 'bg-gray-100 text-gray-800' }}"
                          aria-label="{{ $tabData['count'] }} packages">
                        {{ $tabData['count'] }}
                    </span>
                @endif
                
                <!-- Active Tab Indicator -->
                @if($activeTab === $tabKey)
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-500"
                         aria-hidden="true"></div>
                @endif
                
                <!-- Loading Indicator -->
                <div wire:loading.flex wire:target="switchTab('{{ $tabKey }}')"
                     class="absolute inset-0 bg-white bg-opacity-75 items-center justify-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                </div>
            </button>
        @endforeach
    </div>

    <!-- Tab Content Container -->
    <div id="tab-content"
         class="bg-white rounded-b-lg shadow-sm border border-t-0 border-gray-200 
                min-h-[400px] focus:outline-none" 
         role="tabpanel" 
         aria-labelledby="tab-{{ $activeTab }}"
         aria-live="polite"
         aria-busy="{{ $this->isLoading ? 'true' : 'false' }}"
         tabindex="-1">
        
        <!-- Loading State -->
        <div wire:loading.delay 
             class="flex flex-col items-center justify-center py-16 px-4"
             role="status"
             aria-live="polite">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
            <span class="text-gray-600 font-medium text-center">
                Loading {{ $activeTabData['name'] }}...
            </span>
            <span class="sr-only">Please wait while content loads</span>
        </div>

        <!-- Tab Content -->
        <div wire:loading.remove class="p-4 sm:p-6">
            @if($activeTab === 'consolidated')
                <div class="consolidated-packages-content"
                     id="tabpanel-consolidated"
                     role="tabpanel"
                     aria-labelledby="tab-consolidated"
                     aria-label="Consolidated packages view">
                    @livewire('manifests.consolidated-packages-tab', ['manifest' => $manifest], key('consolidated-'.$manifest->id))
                </div>
            @elseif($activeTab === 'individual')
                <div class="individual-packages-content"
                     id="tabpanel-individual"
                     role="tabpanel"
                     aria-labelledby="tab-individual"
                     aria-label="Individual packages view">
                    @livewire('manifests.individual-packages-tab', ['manifest' => $manifest], key('individual-'.$manifest->id))
                </div>
            @endif
        </div>
        
        <!-- Empty State Message -->
        @if(($activeTab === 'consolidated' && $tabs['consolidated']['count'] === 0) || 
            ($activeTab === 'individual' && $tabs['individual']['count'] === 0))
            <div class="text-center py-12 px-4" role="status">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    No {{ strtolower($activeTabData['name']) }} found
                </h3>
                <p class="text-gray-500">
                    There are currently no {{ strtolower($activeTabData['name']) }} for this manifest.
                </p>
            </div>
        @endif
    </div>

    <!-- Screen Reader Announcements -->
    <div aria-live="assertive" aria-atomic="true" class="sr-only">
        <span x-text="announcement"></span>
    </div>
</div>

@push('scripts')
<script>
function manifestTabs() {
    return {
        announcement: '',
        focusedTabIndex: 0,
        
        init() {
            // Listen for tab switch events
            this.$wire.on('tabSwitched', (tab) => {
                this.announceTabChange(tab);
                this.updateFocus();
                this.updateFocusedTabIndex(tab);
            });
            
            // Listen for URL update events
            window.addEventListener('update-url', (event) => {
                this.updateBrowserUrl(event.detail);
            });
            
            // Listen for browser back/forward navigation
            window.addEventListener('popstate', (event) => {
                if (event.state && event.state.tab) {
                    this.$wire.switchTab(event.state.tab);
                }
            });
            
            // Initialize browser history state
            this.initializeBrowserState();
            
            // Initialize focused tab index
            this.updateFocusedTabIndex(this.$wire.activeTab);
            
            // Handle window resize for responsive behavior
            window.addEventListener('resize', this.handleResize.bind(this));
            
            // Handle touch events for mobile
            this.initializeTouchHandlers();
        },
        
        nextTab() {
            const tabs = ['individual', 'consolidated'];
            const currentIndex = tabs.indexOf(this.$wire.activeTab);
            const nextIndex = (currentIndex + 1) % tabs.length;
            this.focusedTabIndex = nextIndex;
            this.$wire.switchTab(tabs[nextIndex]);
        },
        
        prevTab() {
            const tabs = ['individual', 'consolidated'];
            const currentIndex = tabs.indexOf(this.$wire.activeTab);
            const prevIndex = currentIndex === 0 ? tabs.length - 1 : currentIndex - 1;
            this.focusedTabIndex = prevIndex;
            this.$wire.switchTab(tabs[prevIndex]);
        },
        
        firstTab() {
            this.focusedTabIndex = 0;
            this.$wire.switchTab('individual');
        },
        
        lastTab() {
            this.focusedTabIndex = 1;
            this.$wire.switchTab('consolidated');
        },
        
        updateFocusedTabIndex(tab) {
            const tabs = ['individual', 'consolidated'];
            this.focusedTabIndex = tabs.indexOf(tab);
        },
        
        scrollTabIntoView(tabElement) {
            // Ensure the focused tab is visible in the scrollable container
            if (tabElement && this.$refs.tablist) {
                const container = this.$refs.tablist;
                const containerRect = container.getBoundingClientRect();
                const tabRect = tabElement.getBoundingClientRect();
                
                if (tabRect.left < containerRect.left) {
                    container.scrollLeft -= (containerRect.left - tabRect.left + 10);
                } else if (tabRect.right > containerRect.right) {
                    container.scrollLeft += (tabRect.right - containerRect.right + 10);
                }
            }
        },
        
        announceTabChange(tab) {
            const tabNames = {
                'consolidated': 'Consolidated Packages',
                'individual': 'Individual Packages'
            };
            
            // Get package count for more informative announcement
            const tabData = this.getTabData(tab);
            const count = tabData ? tabData.count : 0;
            const countText = count === 1 ? '1 package' : `${count} packages`;
            
            this.announcement = `Switched to ${tabNames[tab]} tab. ${countText} available.`;
            
            // Clear announcement after a delay
            setTimeout(() => {
                this.announcement = '';
            }, 2000);
        },
        
        getTabData(tab) {
            // This would need to be passed from the component or retrieved from DOM
            const tabElement = document.getElementById(`tab-${tab}`);
            if (tabElement) {
                const badge = tabElement.querySelector('[aria-label*="packages"]');
                if (badge) {
                    const count = parseInt(badge.textContent) || 0;
                    return { count };
                }
            }
            return { count: 0 };
        },
        
        updateFocus() {
            // Update focus to active tab for keyboard navigation
            setTimeout(() => {
                const activeTab = document.querySelector('[role="tab"][aria-selected="true"]');
                if (activeTab) {
                    activeTab.focus();
                    this.scrollTabIntoView(activeTab);
                }
            }, 100);
        },
        
        handleResize() {
            // Handle responsive behavior on window resize
            const tablist = this.$refs.tablist;
            if (tablist) {
                // Reset scroll position if needed
                const activeTab = tablist.querySelector('[aria-selected="true"]');
                if (activeTab) {
                    this.scrollTabIntoView(activeTab);
                }
            }
        },
        
        initializeTouchHandlers() {
            // Add touch support for mobile devices
            let startX = 0;
            let startY = 0;
            let isScrolling = false;
            
            const tablist = this.$refs.tablist;
            if (tablist) {
                tablist.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    startY = e.touches[0].clientY;
                    isScrolling = false;
                }, { passive: true });
                
                tablist.addEventListener('touchmove', (e) => {
                    if (!isScrolling) {
                        const deltaX = Math.abs(e.touches[0].clientX - startX);
                        const deltaY = Math.abs(e.touches[0].clientY - startY);
                        
                        // Determine if this is a horizontal scroll
                        if (deltaX > deltaY) {
                            isScrolling = true;
                        }
                    }
                }, { passive: true });
            }
        },
        
        updateBrowserUrl(detail) {
            const url = new URL(window.location);
            if (detail.tab !== 'individual') {
                url.searchParams.set('activeTab', detail.tab);
            } else {
                url.searchParams.delete('activeTab');
            }
            
            // Update browser history
            window.history.pushState(
                { 
                    tab: detail.tab, 
                    manifestId: detail.manifestId,
                    timestamp: Date.now()
                },
                `${detail.tab.charAt(0).toUpperCase() + detail.tab.slice(1)} Packages - Manifest ${detail.manifestId}`,
                url.toString()
            );
        },
        
        initializeBrowserState() {
            const currentTab = this.$wire.activeTab;
            const manifestId = {{ $manifest->id }};
            
            // Set initial browser state
            window.history.replaceState(
                { 
                    tab: currentTab, 
                    manifestId: manifestId,
                    timestamp: Date.now()
                },
                `${currentTab.charAt(0).toUpperCase() + currentTab.slice(1)} Packages - Manifest ${manifestId}`,
                window.location.href
            );
        }
    }
}

// Global keyboard shortcuts for accessibility
document.addEventListener('keydown', function(e) {
    // Alt + T to focus on tabs
    if (e.altKey && e.key === 't') {
        e.preventDefault();
        const firstTab = document.querySelector('[role="tab"]');
        if (firstTab) {
            firstTab.focus();
        }
    }
});
</script>
@endpush

@push('styles')
<style>
/* Enhanced tab styles with modern design and accessibility */
.manifest-tabs-container {
    @apply max-w-full;
    /* Ensure container doesn't overflow on small screens */
    min-width: 0;
}

/* Skip link styling */
.manifest-tabs-container a[href="#tab-content"] {
    transform: translateY(-100%);
    transition: transform 0.3s ease-in-out;
}

.manifest-tabs-container a[href="#tab-content"]:focus {
    transform: translateY(0);
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
    .manifest-tabs-container [role="tablist"] {
        @apply overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100;
        /* Smooth scrolling on mobile */
        scroll-behavior: smooth;
        /* Snap to tabs for better UX */
        scroll-snap-type: x mandatory;
    }
    
    .manifest-tabs-container [role="tab"] {
        @apply flex-shrink-0 min-w-[120px];
        /* Snap alignment */
        scroll-snap-align: start;
        /* Larger touch targets */
        min-height: 48px;
    }
    
    /* Adjust padding for mobile */
    .manifest-tabs-container #tab-content {
        @apply p-3;
    }
}

/* Tablet responsive adjustments */
@media (min-width: 641px) and (max-width: 1024px) {
    .manifest-tabs-container [role="tab"] {
        @apply px-4 py-3;
        min-height: 44px;
    }
}

/* Enhanced focus states for better accessibility */
.manifest-tabs-container [role="tab"]:focus-visible {
    @apply ring-2 ring-blue-500 ring-offset-2 outline-none z-10;
    /* Ensure focus ring is visible over other elements */
    position: relative;
}

/* Focus within for keyboard navigation */
.manifest-tabs-container [role="tablist"]:focus-within {
    @apply ring-1 ring-blue-300 ring-offset-1;
}

/* Smooth hover transitions with reduced motion support */
.manifest-tabs-container [role="tab"]:hover:not(:disabled) {
    @apply transform scale-[1.02];
    transition: transform 0.15s ease-out, background-color 0.15s ease-out;
}

/* Active tab enhanced styling */
.manifest-tabs-container [role="tab"][aria-selected="true"] {
    @apply shadow-sm relative;
    /* Ensure active tab is above others */
    z-index: 1;
}

/* Disabled state styling */
.manifest-tabs-container [role="tab"]:disabled {
    @apply cursor-not-allowed opacity-50;
    transform: none !important;
}

/* Loading state for tabs */
.manifest-tabs-container [role="tab"] [wire\:loading] {
    @apply absolute inset-0 bg-white bg-opacity-75 rounded;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .manifest-tabs-container [role="tab"] {
        @apply border-2 border-current;
    }
    
    .manifest-tabs-container [role="tab"][aria-selected="true"] {
        @apply bg-current text-white;
    }
    
    .manifest-tabs-container [role="tab"]:focus-visible {
        @apply ring-4 ring-yellow-400;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .manifest-tabs-container [role="tab"] {
        @apply transition-none transform-none;
    }
    
    .manifest-tabs-container [role="tab"]:hover {
        @apply transform-none;
    }
    
    .manifest-tabs-container [role="tablist"] {
        scroll-behavior: auto;
    }
    
    .manifest-tabs-container a[href="#tab-content"] {
        transition: none;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .manifest-tabs-container [role="tablist"] {
        @apply bg-gray-800 border-gray-600;
    }
    
    .manifest-tabs-container [role="tab"] {
        @apply text-gray-300;
    }
    
    .manifest-tabs-container [role="tab"]:hover {
        @apply text-white bg-gray-700;
    }
    
    .manifest-tabs-container [role="tab"][aria-selected="true"] {
        @apply bg-gray-700 text-white border-blue-400;
    }
    
    .manifest-tabs-container #tab-content {
        @apply bg-gray-800 border-gray-600;
    }
}

/* Custom scrollbar for webkit browsers */
.manifest-tabs-container [role="tablist"]::-webkit-scrollbar {
    height: 4px;
}

.manifest-tabs-container [role="tablist"]::-webkit-scrollbar-track {
    @apply bg-gray-100 rounded-full;
}

.manifest-tabs-container [role="tablist"]::-webkit-scrollbar-thumb {
    @apply bg-gray-300 rounded-full;
    transition: background-color 0.2s ease;
}

.manifest-tabs-container [role="tablist"]::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-400;
}

/* Firefox scrollbar styling */
.manifest-tabs-container [role="tablist"] {
    scrollbar-width: thin;
    scrollbar-color: rgb(209 213 219) rgb(243 244 246);
}

/* Touch-friendly adjustments for mobile */
@media (pointer: coarse) {
    .manifest-tabs-container [role="tab"] {
        /* Larger touch targets */
        min-height: 48px;
        @apply px-4 py-3;
    }
    
    /* Remove hover effects on touch devices */
    .manifest-tabs-container [role="tab"]:hover {
        @apply transform-none;
    }
}

/* Print styles */
@media print {
    .manifest-tabs-container [role="tablist"] {
        @apply hidden;
    }
    
    .manifest-tabs-container #tab-content {
        @apply border-0 shadow-none rounded-none;
    }
}

/* Screen reader only content */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.sr-only:focus,
.focus\:not-sr-only:focus {
    position: static;
    width: auto;
    height: auto;
    padding: inherit;
    margin: inherit;
    overflow: visible;
    clip: auto;
    white-space: normal;
}

/* Animation for tab content changes */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.manifest-tabs-container #tab-content > div {
    animation: fadeIn 0.2s ease-out;
}

@media (prefers-reduced-motion: reduce) {
    .manifest-tabs-container #tab-content > div {
        animation: none;
    }
}

/* Ensure proper spacing and alignment */
.manifest-tabs-container [role="tab"] svg {
    flex-shrink: 0;
}

.manifest-tabs-container [role="tab"] span {
    min-width: 0; /* Allow text truncation */
}

/* Loading spinner accessibility */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@media (prefers-reduced-motion: reduce) {
    .animate-spin {
        animation: none;
    }
}
</style>
@endpush