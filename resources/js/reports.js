// Simple reports JavaScript for chart initialization
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh functionality
    let autoRefreshInterval = null;
    
    window.addEventListener('startAutoRefresh', event => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        
        autoRefreshInterval = setInterval(() => {
            Livewire.emit('refreshData');
        }, event.detail.interval || 300000); // Default 5 minutes
    });
    
    window.addEventListener('stopAutoRefresh', event => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    });
    
    // Toast notifications
    window.addEventListener('toastr:success', event => {
        if (typeof toastr !== 'undefined') {
            toastr.success(event.detail.message);
        }
    });
    
    window.addEventListener('toastr:error', event => {
        if (typeof toastr !== 'undefined') {
            toastr.error(event.detail.message);
        }
    });
    
    window.addEventListener('toastr:info', event => {
        if (typeof toastr !== 'undefined') {
            toastr.info(event.detail.message);
        }
    });
});