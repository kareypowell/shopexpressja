@extends('layouts.base')

@section('body')

@if(auth()->user()->hasRole('superadmin'))
<x-sidebar-nav />

<div class="md:pl-64 flex flex-col">

    <x-topbar />

    <main class="flex-1">
        <div class="py-6">
            @isset($title)
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $title }}</h1>
            </div>
            @endisset

            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                @yield('content')

                @isset($slot)
                {{ $slot }}
                @endisset
            </div>
        </div>
    </main>
</div>
@else
<div class="min-h-full">
    <x-navbar />

    <header class="bg-white shadow">
        @isset($title)
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold leading-tight tracking-tight text-gray-900">{{ $title }}</h1>
        </div>
        @endisset
    </header>

    <main>
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')

            @isset($slot)
            {{ $slot }}
            @endisset
        </div>
    </main>

    {{-- <x-footer /> --}}
</div>
@endif

@endsection