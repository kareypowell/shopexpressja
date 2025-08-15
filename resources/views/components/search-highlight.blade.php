@props(['text', 'search', 'matches' => []])

@php
    $highlightedText = $text;
    
    if (!empty($search) && !empty($matches)) {
        foreach ($matches as $match) {
            if ($match['field'] === 'tracking_number' || $match['field'] === 'consolidated_tracking_number') {
                $highlightedText = str_ireplace(
                    $search, 
                    '<mark class="bg-yellow-200 text-yellow-900 px-1 rounded">' . $search . '</mark>', 
                    $highlightedText
                );
            } elseif ($match['field'] === 'description' || $match['field'] === 'individual_description') {
                $highlightedText = str_ireplace(
                    $search, 
                    '<mark class="bg-yellow-200 text-yellow-900 px-1 rounded">' . $search . '</mark>', 
                    $highlightedText
                );
            }
        }
    }
@endphp

<span {!! $attributes !!}>{!! $highlightedText !!}</span>