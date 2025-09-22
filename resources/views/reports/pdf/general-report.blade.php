@extends('reports.pdf.layout')

@section('content')
    {{-- Summary Section --}}
    @if(isset($summary) && !empty($summary))
        <div class="section">
            <h2>Report Summary</h2>
            <div class="summary-grid">
                @foreach($summary as $key => $value)
                    <div class="summary-card">
                        <h4>{{ ucwords(str_replace('_', ' ', $key)) }}</h4>
                        <div class="value text-blue">
                            @if(is_numeric($value))
                                {{ number_format($value, 2) }}
                            @else
                                {{ $value }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Charts Section --}}
    @if(isset($charts) && !empty($charts))
        <div class="section">
            <h2>Visual Analytics</h2>
            @foreach($charts as $chartKey => $chartData)
                <div class="chart-container">
                    <div class="chart-title">{{ ucwords(str_replace('_', ' ', $chartKey)) }}</div>
                    <img src="{{ $chartData }}" alt="{{ ucwords(str_replace('_', ' ', $chartKey)) }} Chart">
                </div>
            @endforeach
        </div>
    @endif

    {{-- Data Table --}}
    @if(isset($records) && !empty($records))
        <div class="section">
            <h2>Detailed Data</h2>
            @php
                $firstRecord = $records[0] ?? [];
                $headers = is_array($firstRecord) ? array_keys($firstRecord) : [];
            @endphp
            
            @if(!empty($headers))
                <table>
                    <thead>
                        <tr>
                            @foreach($headers as $header)
                                <th class="{{ in_array(strtolower($header), ['amount', 'price', 'cost', 'total', 'balance']) ? 'text-right' : '' }}">
                                    {{ ucwords(str_replace('_', ' ', $header)) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                            <tr>
                                @foreach($headers as $header)
                                    @php
                                        $value = is_array($record) ? ($record[$header] ?? '') : (is_object($record) ? ($record->$header ?? '') : '');
                                        $isNumeric = is_numeric($value);
                                        $isMoney = in_array(strtolower($header), ['amount', 'price', 'cost', 'total', 'balance']);
                                    @endphp
                                    <td class="{{ $isMoney ? 'text-right' : '' }}">
                                        @if($isMoney && $isNumeric)
                                            ${{ number_format($value, 2) }}
                                        @elseif($isNumeric)
                                            {{ number_format($value, 2) }}
                                        @elseif(strtotime($value))
                                            {{ \Carbon\Carbon::parse($value)->format('M j, Y') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- Additional Data Sections --}}
    @if(isset($additional_data) && !empty($additional_data))
        @foreach($additional_data as $sectionKey => $sectionData)
            <div class="section">
                <h2>{{ ucwords(str_replace('_', ' ', $sectionKey)) }}</h2>
                
                @if(is_array($sectionData) && !empty($sectionData))
                    @php
                        $firstItem = $sectionData[0] ?? [];
                        $sectionHeaders = is_array($firstItem) ? array_keys($firstItem) : [];
                    @endphp
                    
                    @if(!empty($sectionHeaders))
                        <table>
                            <thead>
                                <tr>
                                    @foreach($sectionHeaders as $header)
                                        <th class="{{ in_array(strtolower($header), ['amount', 'price', 'cost', 'total', 'balance']) ? 'text-right' : '' }}">
                                            {{ ucwords(str_replace('_', ' ', $header)) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sectionData as $item)
                                    <tr>
                                        @foreach($sectionHeaders as $header)
                                            @php
                                                $value = is_array($item) ? ($item[$header] ?? '') : (is_object($item) ? ($item->$header ?? '') : '');
                                                $isNumeric = is_numeric($value);
                                                $isMoney = in_array(strtolower($header), ['amount', 'price', 'cost', 'total', 'balance']);
                                            @endphp
                                            <td class="{{ $isMoney ? 'text-right' : '' }}">
                                                @if($isMoney && $isNumeric)
                                                    ${{ number_format($value, 2) }}
                                                @elseif($isNumeric)
                                                    {{ number_format($value, 2) }}
                                                @elseif(strtotime($value))
                                                    {{ \Carbon\Carbon::parse($value)->format('M j, Y') }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>No data available for this section.</p>
                    @endif
                @else
                    <p>{{ $sectionData }}</p>
                @endif
            </div>
        @endforeach
    @endif

    {{-- Filter Information --}}
    @if(isset($filters) && !empty($filters))
        <div class="section">
            <h2>Report Filters</h2>
            <table>
                <tbody>
                    @foreach($filters as $filterKey => $filterValue)
                        @if(!empty($filterValue))
                            <tr>
                                <td class="font-bold" style="width: 150px;">{{ ucwords(str_replace('_', ' ', $filterKey)) }}:</td>
                                <td>{{ is_array($filterValue) ? implode(', ', $filterValue) : $filterValue }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection