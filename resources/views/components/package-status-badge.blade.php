@props(['status', 'package' => null])

@php
    // Handle both string status and Package model
    if ($package) {
        $badgeClass = $package->status_badge_class ?? 'default';
        $statusLabel = $package->status_label ?? 'Unknown';
    } else {
        // Handle direct status string
        try {
            $statusEnum = \App\Enums\PackageStatus::from($status);
            $badgeClass = $statusEnum->getBadgeClass();
            $statusLabel = $statusEnum->getLabel();
        } catch (\Exception $e) {
            $badgeClass = 'default';
            $statusLabel = ucfirst($status ?? 'Unknown');
        }
    }
@endphp

@if($badgeClass === 'default')
    <x-badges.default>{{ $statusLabel }}</x-badges.default>
@elseif($badgeClass === 'primary')
    <x-badges.primary>{{ $statusLabel }}</x-badges.primary>
@elseif($badgeClass === 'success')
    <x-badges.success>{{ $statusLabel }}</x-badges.success>
@elseif($badgeClass === 'warning')
    <x-badges.warning>{{ $statusLabel }}</x-badges.warning>
@elseif($badgeClass === 'danger')
    <x-badges.danger>{{ $statusLabel }}</x-badges.danger>
@elseif($badgeClass === 'shs')
    <x-badges.shs>{{ $statusLabel }}</x-badges.shs>
@else
    <x-badges.default>{{ $statusLabel }}</x-badges.default>
@endif