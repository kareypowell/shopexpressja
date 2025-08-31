<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Compose Message</h2>

                <!-- Flash Messages -->
                @if (session()->has('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <!-- Main Composition Area -->
                    <div class="xl:col-span-2">
                        <form wire:submit.prevent="showPreview">
                            <!-- Subject -->
                            <div class="mb-6">
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                    Subject <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="subject"
                                    wire:model.defer="subject"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Enter message subject..."
                                >
                                @error('subject') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                @enderror
                            </div>

                            <!-- Message Content -->
                            <div class="mb-6" wire:ignore>
                                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                                    Message Content <span class="text-red-500">*</span>
                                </label>
                                <div class="border border-gray-300 rounded-md">
                                    <textarea 
                                        id="content"
                                        wire:model.defer="content"
                                        rows="12"
                                        class="w-full px-3 py-2 border-0 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                        placeholder="Enter your message content here..."
                                    ></textarea>
                                </div>
                                @error('content') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                @enderror
                                <div class="mt-2 text-sm text-gray-500">
                                    <p class="mb-1">Use the rich text editor to format your message content.</p>
                                    <p><strong>Personalization:</strong> Type <code class="bg-gray-100 px-1 rounded">@</code> to insert customer placeholders like <code class="bg-gray-100 px-1 rounded">{customer.first_name}</code>, or use the "Placeholders" button in the toolbar.</p>
                                </div>
                            </div>

                            <!-- Scheduling Options -->
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center mb-4">
                                    <input 
                                        type="checkbox" 
                                        id="isScheduled"
                                        wire:model="isScheduled"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                    <label for="isScheduled" class="ml-2 block text-sm font-medium text-gray-700">
                                        Schedule for later
                                    </label>
                                </div>

                                @if($isScheduled)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="scheduledDate" class="block text-sm font-medium text-gray-700 mb-1">
                                                Date <span class="text-red-500">*</span>
                                            </label>
                                            <input 
                                                type="date" 
                                                id="scheduledDate"
                                                wire:model.defer="scheduledDate"
                                                min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                            @error('scheduledDate') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="scheduledTime" class="block text-sm font-medium text-gray-700 mb-1">
                                                Time <span class="text-red-500">*</span>
                                            </label>
                                            <input 
                                                type="time" 
                                                id="scheduledTime"
                                                wire:model.defer="scheduledTime"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                            @error('scheduledTime') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3">
                                <button 
                                    type="button"
                                    wire:click="saveDraft"
                                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    Save Draft
                                </button>
                                
                                <button 
                                    type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Preview Message
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Recipient Selection Sidebar -->
                    <div class="xl:col-span-1">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Recipients</h3>
                            
                            <!-- Recipient Type Selection -->
                            <div class="mb-4">
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            wire:model="recipientType" 
                                            value="all"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">All Customers</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            wire:model="recipientType" 
                                            value="selected"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">Selected Customers</span>
                                    </label>
                                </div>
                                @error('recipientType') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                @enderror
                            </div>

                            <!-- Recipient Count -->
                            <div class="mb-4 p-3 bg-white rounded border">
                                <div class="text-sm text-gray-600">Recipients:</div>
                                <div class="text-2xl font-bold text-indigo-600">{{ number_format($recipientCount) }}</div>
                            </div>

                            <!-- Customer Selection Interface -->
                            @if($recipientType === 'selected')
                                <div class="space-y-4">
                                    <!-- Search -->
                                    <div>
                                        <label for="customerSearch" class="block text-sm font-medium text-gray-700 mb-1">
                                            Search Customers
                                        </label>
                                        <input 
                                            type="text" 
                                            id="customerSearch"
                                            wire:model.debounce.300ms="customerSearch"
                                            placeholder="Search by name, email..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                    </div>

                                    <!-- Selection Actions -->
                                    <div class="flex gap-2">
                                        <button 
                                            type="button"
                                            wire:click="selectAllCustomers"
                                            class="flex-1 px-3 py-2 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 transition-colors duration-200"
                                        >
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Select All
                                        </button>
                                        <button 
                                            type="button"
                                            wire:click="clearSelection"
                                            class="flex-1 px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors duration-200"
                                        >
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Clear All
                                        </button>
                                    </div>

                                    <!-- Customer List -->
                                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded">
                                        @forelse($availableCustomers as $customer)
                                            <div class="flex items-center p-2 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                                <input 
                                                    type="checkbox" 
                                                    wire:click="toggleCustomer({{ $customer->id }})"
                                                    @if(in_array($customer->id, $selectedCustomers)) checked @endif
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                >
                                                <div class="ml-2 flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 truncate">
                                                        {{ $customer->full_name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 truncate">
                                                        {{ $customer->email }}
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-4 text-center text-gray-500 text-sm">
                                                No customers found
                                            </div>
                                        @endforelse
                                    </div>

                                    <!-- Pagination -->
                                    @if($availableCustomers->hasPages())
                                        <div class="mt-4">
                                            {{ $availableCustomers->links() }}
                                        </div>
                                    @endif

                                    @error('selectedCustomers') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                    @enderror
                                </div>
                            @endif

                            <!-- Placeholder Reference -->
                            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <h4 class="text-sm font-semibold text-blue-900 mb-2">Available Placeholders</h4>
                                <div class="text-xs text-blue-800 space-y-1">
                                    <div><code class="bg-blue-100 px-1 rounded">{customer.first_name}</code> - Customer's first name</div>
                                    <div><code class="bg-blue-100 px-1 rounded">{customer.full_name}</code> - Customer's full name</div>
                                    <div><code class="bg-blue-100 px-1 rounded">{customer.email}</code> - Customer's email</div>
                                    <div><code class="bg-blue-100 px-1 rounded">{company.name}</code> - Company name</div>
                                    <div><code class="bg-blue-100 px-1 rounded">{current.date}</code> - Current date</div>
                                    <div class="text-blue-600 mt-2">
                                        <strong>Tip:</strong> Type <code class="bg-blue-100 px-1 rounded">@</code> in the editor or use the "Placeholders" button
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    @if($showPreview)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="hidePreview">
            <div class="relative top-4 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 xl:w-2/3 max-w-4xl shadow-lg rounded-md bg-white mb-8" wire:click.stop>
                <div class="mt-3">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b">
                        <h3 class="text-xl font-semibold text-gray-900">Preview Message</h3>
                        <button 
                            wire:click="hidePreview"
                            class="text-gray-400 hover:text-gray-600 transition-colors duration-200"
                            aria-label="Close preview"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Preview Content -->
                    <div class="mt-6">
                        <!-- Message Summary -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg mb-6 border border-blue-200">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Message Summary</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <div>
                                        <span class="font-medium text-gray-700">Recipients:</span>
                                        <span class="text-gray-900 ml-1">{{ number_format($recipientCount) }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <span class="font-medium text-gray-700">Type:</span>
                                        <span class="text-gray-900 ml-1">{{ ucfirst($recipientType) }} customers</span>
                                    </div>
                                </div>
                                @if($isScheduled)
                                    <div class="flex items-center sm:col-span-2 lg:col-span-1">
                                        <svg class="w-4 h-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div>
                                            <span class="font-medium text-gray-700">Scheduled:</span>
                                            <span class="text-gray-900 ml-1">
                                                {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i', $scheduledDate . ' ' . $scheduledTime)->format('M j, Y \a\t g:i A') }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Validation Status -->
                            @if($recipientCount === 0)
                                <div class="mt-3 p-3 bg-red-100 border border-red-300 rounded-md">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <span class="text-red-700 text-sm font-medium">Warning: No recipients selected</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Email Preview -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                            <!-- Email Header -->
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">From:</span> {{ config('app.name') }} &lt;{{ env('ADMIN_EMAIL') }}&gt;
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Email Preview
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="text-lg font-semibold text-gray-900">{{ $this->previewContent['subject'] }}</div>
                                    @if($this->previewContent['sample_customer'])
                                        <div class="text-xs text-blue-600 mt-1">
                                            Preview personalized for: {{ $this->previewContent['sample_customer']->full_name }}
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-500 mt-1">
                                            Preview with sample data (no customers selected)
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Email Body -->
                            <div class="p-6 bg-white">
                                <div class="prose prose-sm sm:prose lg:prose-lg max-w-none">
                                    {!! $this->previewContent['content'] !!}
                                </div>
                                
                                <!-- Email Footer -->
                                <div class="mt-8 pt-4 border-t border-gray-200">
                                    <div class="text-xs text-gray-500">
                                        <p>This message was sent to you as a customer of {{ config('app.name') }}.</p>
                                        <p class="mt-1">If you have any questions, please contact us at {{ env('ADMIN_EMAIL') }}.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 mt-6 pt-4 border-t">
                        <div class="text-sm text-gray-600 order-2 sm:order-1">
                            @if($recipientCount > 0)
                                Ready to send to {{ number_format($recipientCount) }} recipient{{ $recipientCount !== 1 ? 's' : '' }}
                            @else
                                <span class="text-red-600">Please select recipients before sending</span>
                            @endif
                        </div>
                        
                        <div class="flex gap-3 order-1 sm:order-2">
                            <button 
                                wire:click="hidePreview"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                                </svg>
                                Edit Message
                            </button>
                            
                            @if($recipientCount > 0)
                                @if($isScheduled)
                                    <button 
                                        wire:click="scheduleMessage"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Schedule Message
                                    </button>
                                @else
                                    <button 
                                        wire:click="sendNow"
                                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        Send Now
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- TinyMCE Script -->
    <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Available placeholders for customer personalization
        const availablePlaceholders = [
            { text: '{customer.first_name}', value: '{customer.first_name}', description: 'Customer first name' },
            { text: '{customer.last_name}', value: '{customer.last_name}', description: 'Customer last name' },
            { text: '{customer.full_name}', value: '{customer.full_name}', description: 'Customer full name' },
            { text: '{customer.email}', value: '{customer.email}', description: 'Customer email address' },
            { text: '{customer.phone}', value: '{customer.phone}', description: 'Customer phone number' },
            { text: '{customer.address}', value: '{customer.address}', description: 'Customer address' },
            { text: '{customer.city}', value: '{customer.city}', description: 'Customer city' },
            { text: '{customer.country}', value: '{customer.country}', description: 'Customer country' },
            { text: '{company.name}', value: '{company.name}', description: 'Company name' },
            { text: '{company.email}', value: '{company.email}', description: 'Company email' },
            { text: '{current.date}', value: '{current.date}', description: 'Current date' },
            { text: '{current.time}', value: '{current.time}', description: 'Current time' }
        ];

        document.addEventListener('DOMContentLoaded', function() {
            tinymce.init({
                selector: '#content',
                height: 400,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'mentions'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic forecolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'placeholders | removeformat | help',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; } .placeholder { background-color: #e3f2fd; padding: 2px 4px; border-radius: 3px; color: #1976d2; font-weight: bold; }',
                branding: false,
                promotion: false,
                skin: 'oxide',
                content_css: 'default',
                valid_elements: 'p,br,strong,em,u,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|title],img[src|alt|width|height],table,thead,tbody,tr,th,td,blockquote,div[class],span[class]',
                invalid_elements: 'script,object,embed,iframe',
                mentions: {
                    source: availablePlaceholders.map(placeholder => ({
                        id: placeholder.value,
                        text: placeholder.text,
                        description: placeholder.description
                    })),
                    delimiter: '@',
                    insert: function (item) {
                        return '<span class="placeholder">' + item.id + '</span>';
                    }
                },
                
                setup: function (editor) {
                    // Add custom placeholder button
                    editor.ui.registry.addMenuButton('placeholders', {
                        text: 'Placeholders',
                        icon: 'template',
                        fetch: function (callback) {
                            const items = availablePlaceholders.map(placeholder => ({
                                type: 'menuitem',
                                text: placeholder.text,
                                onAction: function () {
                                    editor.insertContent('<span class="placeholder">' + placeholder.value + '</span>');
                                }
                            }));
                            callback(items);
                        }
                    });

                    // Sync content with Livewire
                    editor.on('change', function () {
                        window.livewire.find('{{ $this->id }}').set('content', editor.getContent());
                    });
                    
                    editor.on('keyup', function () {
                        window.livewire.find('{{ $this->id }}').set('content', editor.getContent());
                    });

                    // Listen for Livewire updates to sync content
                    window.addEventListener('livewire:load', function () {
                        Livewire.hook('message.processed', (message, component) => {
                            if (component.fingerprint.name === 'admin.broadcast-composer') {
                                const currentContent = editor.getContent();
                                const livewireContent = window.livewire.find('{{ $this->id }}').get('content');
                                if (currentContent !== livewireContent) {
                                    editor.setContent(livewireContent || '');
                                }
                            }
                        });
                    });
                },
                
                init_instance_callback: function(editor) {
                    // Set initial content from Livewire
                    const initialContent = window.livewire.find('{{ $this->id }}').get('content');
                    if (initialContent) {
                        editor.setContent(initialContent);
                    }
                }
            });
        });
    </script>
</div>