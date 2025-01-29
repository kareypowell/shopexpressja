@extends('layouts.base')

@section('body')

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
@endsection