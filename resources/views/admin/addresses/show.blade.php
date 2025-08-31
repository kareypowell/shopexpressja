@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Breadcrumb Navigation -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <a href="{{ route('admin.addresses.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">Shipping Addresses</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">{{ $address->street_address }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-12 w-12">
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="flex items-center">
                        <h1 class="text-3xl font-bold text-gray-900">{{ $address->street_address }}</h1>
                        @if($address->is_primary)
                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Primary Address
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-gray-600">Address Details and Information</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.addresses.edit', $address) }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Address
                </a>
                <a href="{{ route('admin.addresses.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Addresses
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md" 
             x-data="{ show: true }" 
             x-show="show" 
             x-transition>
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-700 hover:text-green-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Address Information -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Address Information</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Street Address</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $address->street_address }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">City</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $address->city }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">State/Province</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $address->state }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ZIP/Postal Code</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $address->zip_code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Country</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $address->country }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Primary Address</dt>
                            <dd class="mt-1">
                                @if($address->is_primary)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                        No
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $address->created_at->format('F j, Y \a\t g:i A') }}</dd>
                        </div>
                        @if($address->updated_at && $address->updated_at != $address->created_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $address->updated_at->format('F j, Y \a\t g:i A') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <!-- Address Status -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Address Status</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Primary Status -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full {{ $address->is_primary ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                                        @if($address->is_primary)
                                            <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Primary Address</p>
                                    <p class="text-xs text-gray-500">{{ $address->is_primary ? 'This is the primary address' : 'Secondary address' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold {{ $address->is_primary ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $address->is_primary ? 'YES' : 'NO' }}
                                </p>
                            </div>
                        </div>

                        <!-- Address Completeness -->
                        @php
                            $completeness = 0;
                            $fields = ['street_address', 'city', 'state', 'zip_code', 'country'];
                            foreach($fields as $field) {
                                if(!empty($address->$field)) $completeness++;
                            }
                            $completenessPercent = ($completeness / count($fields)) * 100;
                        @endphp
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full {{ $completenessPercent == 100 ? 'bg-blue-100' : 'bg-yellow-100' }} flex items-center justify-center">
                                        <svg class="h-5 w-5 {{ $completenessPercent == 100 ? 'text-blue-600' : 'text-yellow-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Completeness</p>
                                    <p class="text-xs text-gray-500">{{ $completeness }}/{{ count($fields) }} fields filled</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold {{ $completenessPercent == 100 ? 'text-blue-600' : 'text-yellow-600' }}">
                                    {{ number_format($completenessPercent) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Completeness Progress Bar -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Address Completeness</span>
                            <span class="text-sm text-gray-500">{{ $completeness }}/{{ count($fields) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $completenessPercent }}%"></div>
                        </div>
                        @if($completenessPercent < 100)
                            <p class="text-xs text-gray-500 mt-2">Some address fields are missing information</p>
                        @else
                            <p class="text-xs text-green-600 mt-2">All address fields are complete</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Address Display -->
    <div class="mt-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Formatted Address</h3>
                <p class="mt-1 text-sm text-gray-600">Complete address as it would appear on shipping labels</p>
            </div>
            <div class="p-6">
                <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <div class="text-lg font-medium text-gray-900 mb-2">
                            @if($address->is_primary)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mb-2">
                                    PRIMARY ADDRESS
                                </span><br>
                            @endif
                            {{ $address->street_address }}
                        </div>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div>{{ $address->city }}, {{ $address->state }} {{ $address->zip_code }}</div>
                            <div class="font-medium">{{ $address->country }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Section -->
    <div class="mt-8">
        <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Address Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.addresses.edit', $address) }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Address
                </a>
                
                <button onclick="confirmDelete()" 
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Address
                </button>

                @if(!$address->is_primary)
                <button onclick="makePrimary()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Make Primary
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this address? This action cannot be undone.')) {
        // Create a form and submit it for deletion
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.addresses.destroy", $address) }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}

@if(!$address->is_primary)
function makePrimary() {
    if (confirm('Are you sure you want to make this the primary address? This will remove the primary status from any other address.')) {
        // Create a form and submit it for making primary
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.addresses.update", $address) }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'PUT';
        
        const primaryField = document.createElement('input');
        primaryField.type = 'hidden';
        primaryField.name = 'is_primary';
        primaryField.value = '1';
        
        // Add all current address fields to maintain data
        const fields = {
            'street_address': '{{ $address->street_address }}',
            'city': '{{ $address->city }}',
            'state': '{{ $address->state }}',
            'zip_code': '{{ $address->zip_code }}',
            'country': '{{ $address->country }}'
        };
        
        Object.keys(fields).forEach(key => {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = key;
            field.value = fields[key];
            form.appendChild(field);
        });
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        form.appendChild(primaryField);
        document.body.appendChild(form);
        form.submit();
    }
}
@endif
</script>
@endsection