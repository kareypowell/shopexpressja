<div class="manifest-tabs-container" x-data="manifestTabs()" x-init="init()">
    <!-- Tab Navigation -->
    <div role="tablist" 
         class="tabs tabs-lifted tabs-lg w-full bg-base-100 border-b border-base-300"
         aria-label="Manifest package views">
        
        @foreach($tabs as $tabKey => $tabData)
            <button role="tab" 
                    class="tab tab-lg {{ $activeTab === $tabKey ? 'tab-active' : '' }} 
                           flex items-center gap-2 px-4 py-3 transition-all duration-200
                           hover:bg-base-200 focus:bg-base-200 focus:outline-none focus:ring-2 focus:ring-primary"
                    wire:click="switchTab('{{ $tabKey }}')"
                    aria-label="{{ $tabData['aria_label'] }}"
                    aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                    tabindex="{{ $activeTab === $tabKey ? '0' : '-1' }}"
                    @keydown.arrow-right.prevent="nextTab()"
                    @keydown.arrow-left.prevent="prevTab()"
                    @keydown.home.prevent="firstTab()"
                    @keydown.end.prevent="lastTab()">
                
                <!-- Tab Icon -->
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($tabData['icon'] === 'archive-box')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    @elseif($tabData['icon'] === 'cube')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    @endif
                </svg>
                
                <!-- Tab Label -->
                <span class="hidden sm:inline">{{ $tabData['name'] }}</span>
                <span class="sm:hidden">{{ explode(' ', $tabData['name'])[0] }}</span>
                
                <!-- Package Count Badge -->
                @if($tabData['count'] > 0)
                    <span class="badge badge-primary badge-sm">{{ $tabData['count'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <!-- Tab Content Container -->
    <div class="tab-content-container mt-6 min-h-[400px]" 
         role="tabpanel" 
         aria-labelledby="tab-{{ $activeTab }}"
         aria-live="polite">
        
        <!-- Loading State -->
        <div wire:loading.delay class="flex items-center justify-center py-12">
            <div class="loading loading-spinner loading-lg text-primary"></div>
            <span class="ml-3 text-base-content/70">Loading {{ $activeTabData['name'] }}...</span>
        </div>

        <!-- Tab Content -->
        <div wire:loading.remove>
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
            const tabs = ['consolidated', 'individual'];
            const currentIndex = tabs.indexOf(@entangle('activeTab'));
            const nextIndex = (currentIndex + 1) % tabs.length;
            this.$wire.switchTab(tabs[nextIndex]);
        },
        
        prevTab() {
            const tabs = ['consolidated', 'individual'];
            const currentIndex = tabs.indexOf(@entangle('activeTab'));
            const prevIndex = currentIndex === 0 ? tabs.length - 1 : currentIndex - 1;
            this.$wire.switchTab(tabs[prevIndex]);
        },
        
        firstTab() {
            this.$wire.switchTab('consolidated');
        },
        
        lastTab() {
            this.$wire.switchTab('individual');
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
            if (detail.tab !== 'consolidated') {
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
            const currentTab = @entangle('activeTab');
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
/* Custom tab styles for better accessibility and responsiveness */
.manifest-tabs-container .tab {
    transition: all 0.2s ease-in-out;
}

.manifest-tabs-container .tab:focus {
    outline: 2px solid hsl(var(--p));
    outline-offset: 2px;
}

.manifest-tabs-container .tab-active {
    background-color: hsl(var(--b1));
    border-bottom-color: hsl(var(--b1));
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
    .manifest-tabs-container .tabs {
        overflow-x: auto;
        scrollbar-width: thin;
    }
    
    .manifest-tabs-container .tab {
        flex-shrink: 0;
        min-width: 120px;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .manifest-tabs-container .tab {
        border: 2px solid currentColor;
    }
    
    .manifest-tabs-container .tab-active {
        background-color: currentColor;
        color: hsl(var(--b1));
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .manifest-tabs-container .tab {
        transition: none;
    }
}

/* Focus visible for better keyboard navigation */
.manifest-tabs-container .tab:focus-visible {
    outline: 2px solid hsl(var(--p));
    outline-offset: 2px;
}
</style>
@endpush