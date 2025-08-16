<div class="manifest-tabs-container" x-data="manifestTabs()" x-init="init()">
    <!-- Tab Navigation -->
    <div role="tablist" 
         class="flex bg-white border-b border-gray-200 shadow-sm rounded-t-lg overflow-hidden"
         aria-label="Manifest package views">
        
        @foreach($tabs as $tabKey => $tabData)
            <button role="tab" 
                    class="relative flex items-center gap-3 px-6 py-4 text-sm font-medium transition-all duration-200 ease-in-out
                           {{ $activeTab === $tabKey 
                              ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-500' 
                              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset
                           first:rounded-tl-lg last:rounded-tr-lg"
                    wire:click="switchTab('{{ $tabKey }}')"
                    aria-label="{{ $tabData['aria_label'] }}"
                    aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                    tabindex="{{ $activeTab === $tabKey ? '0' : '-1' }}"
                    @keydown.arrow-right.prevent="nextTab()"
                    @keydown.arrow-left.prevent="prevTab()"
                    @keydown.home.prevent="firstTab()"
                    @keydown.end.prevent="lastTab()">
                
                <!-- Tab Icon -->
                <svg class="w-5 h-5 {{ $activeTab === $tabKey ? 'text-blue-600' : 'text-gray-400' }}" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($tabData['icon'] === 'archive-box')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    @elseif($tabData['icon'] === 'cube')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    @endif
                </svg>
                
                <!-- Tab Label -->
                <span class="hidden sm:inline font-semibold">{{ $tabData['name'] }}</span>
                <span class="sm:hidden font-semibold">{{ explode(' ', $tabData['name'])[0] }}</span>
                
                <!-- Package Count Badge -->
                @if($tabData['count'] > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                 {{ $activeTab === $tabKey 
                                    ? 'bg-blue-100 text-blue-800' 
                                    : 'bg-gray-100 text-gray-800' }}">
                        {{ $tabData['count'] }}
                    </span>
                @endif
                
                <!-- Active Tab Indicator -->
                @if($activeTab === $tabKey)
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-500"></div>
                @endif
            </button>
        @endforeach
    </div>

    <!-- Tab Content Container -->
    <div class="bg-white rounded-b-lg shadow-sm border border-t-0 border-gray-200 min-h-[400px]" 
         role="tabpanel" 
         aria-labelledby="tab-{{ $activeTab }}"
         aria-live="polite">
        
        <!-- Loading State -->
        <div wire:loading.delay class="flex items-center justify-center py-16">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600 font-medium">Loading {{ $activeTabData['name'] }}...</span>
        </div>

        <!-- Tab Content -->
        <div wire:loading.remove class="p-6">
            @if($activeTab === 'consolidated')
                <div class="consolidated-packages-content">
                    @livewire('manifests.consolidated-packages-tab', ['manifest' => $manifest], key('consolidated-'.$manifest->id))
                </div>
            @elseif($activeTab === 'individual')
                <div class="individual-packages-content">
                    @livewire('manifests.individual-packages-tab', ['manifest' => $manifest], key('individual-'.$manifest->id))
                </div>
            @endif
        </div>
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
        
        init() {
            // Listen for tab switch events
            this.$wire.on('tabSwitched', (tab) => {
                this.announceTabChange(tab);
                this.updateFocus();
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
        },
        
        nextTab() {
            const tabs = ['individual', 'consolidated'];
            const currentIndex = tabs.indexOf(this.$wire.activeTab);
            const nextIndex = (currentIndex + 1) % tabs.length;
            this.$wire.switchTab(tabs[nextIndex]);
        },
        
        prevTab() {
            const tabs = ['individual', 'consolidated'];
            const currentIndex = tabs.indexOf(this.$wire.activeTab);
            const prevIndex = currentIndex === 0 ? tabs.length - 1 : currentIndex - 1;
            this.$wire.switchTab(tabs[prevIndex]);
        },
        
        firstTab() {
            this.$wire.switchTab('individual');
        },
        
        lastTab() {
            this.$wire.switchTab('consolidated');
        },
        
        announceTabChange(tab) {
            const tabNames = {
                'consolidated': 'Consolidated Packages',
                'individual': 'Individual Packages'
            };
            this.announcement = `Switched to ${tabNames[tab]} tab`;
            
            // Clear announcement after a delay
            setTimeout(() => {
                this.announcement = '';
            }, 1000);
        },
        
        updateFocus() {
            // Update focus to active tab for keyboard navigation
            setTimeout(() => {
                const activeTab = document.querySelector('[role="tab"][aria-selected="true"]');
                if (activeTab) {
                    activeTab.focus();
                }
            }, 100);
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
                { tab: detail.tab, manifestId: detail.manifestId },
                '',
                url.toString()
            );
        },
        
        initializeBrowserState() {
            const currentTab = this.$wire.activeTab;
            const manifestId = {{ $manifest->id }};
            
            // Set initial browser state
            window.history.replaceState(
                { tab: currentTab, manifestId: manifestId },
                '',
                window.location.href
            );
        }
    }
}
</script>
@endpush

@push('styles')
<style>
/* Enhanced tab styles with modern design */
.manifest-tabs-container {
    @apply max-w-full;
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
    .manifest-tabs-container [role="tablist"] {
        @apply overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100;
    }
    
    .manifest-tabs-container [role="tab"] {
        @apply flex-shrink-0 min-w-[120px];
    }
}

/* Enhanced focus states for better accessibility */
.manifest-tabs-container [role="tab"]:focus-visible {
    @apply ring-2 ring-blue-500 ring-offset-2 outline-none;
}

/* Smooth hover transitions */
.manifest-tabs-container [role="tab"]:hover {
    @apply transform scale-[1.02];
}

/* Active tab enhanced styling */
.manifest-tabs-container [role="tab"][aria-selected="true"] {
    @apply shadow-sm;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .manifest-tabs-container [role="tab"] {
        @apply border-2 border-current;
    }
    
    .manifest-tabs-container [role="tab"][aria-selected="true"] {
        @apply bg-current text-white;
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
}

/* Custom scrollbar for webkit browsers */
.manifest-tabs-container [role="tablist"]::-webkit-scrollbar {
    height: 4px;
}

.manifest-tabs-container [role="tablist"]::-webkit-scrollbar-track {
    @apply bg-gray-100;
}

.manifest-tabs-container [role="tablist"]::-webkit-scrollbar-thumb {
    @apply bg-gray-300 rounded-full;
}

.manifest-tabs-container [role="tablist"]::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-400;
}
</style>
@endpush