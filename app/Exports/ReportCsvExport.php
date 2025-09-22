<?php

namespace App\Exports;

class ReportCsvExport
{
    protected $data;
    protected $headers;
    protected $reportType;

    public function __construct(array $data, array $headers, string $reportType = 'general')
    {
        $this->data = $data;
        $this->headers = $headers;
        $this->reportType = $reportType;
    }

    /**
     * Generate CSV content as string
     */
    public function toCsv(): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, $this->headers);
        
        // Write data rows
        $formattedData = $this->formatDataForExport();
        foreach ($formattedData as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }

    /**
     * Save CSV to file
     */
    public function store(string $filePath): bool
    {
        $csvContent = $this->toCsv();
        return file_put_contents(storage_path('app/' . $filePath), $csvContent) !== false;
    }

    /**
     * Format data for export based on report type
     */
    protected function formatDataForExport(): array
    {
        $formattedData = [];

        foreach ($this->data as $row) {
            $formattedRow = [];
            
            foreach ($this->headers as $header) {
                $value = $this->getValueForHeader($row, $header);
                $formattedRow[] = $this->formatValue($value, $header);
            }
            
            $formattedData[] = $formattedRow;
        }

        return $formattedData;
    }

    /**
     * Get value for specific header from row data
     */
    protected function getValueForHeader($row, string $header): mixed
    {
        // Convert header to potential array key
        $key = $this->headerToKey($header);
        
        // Try direct key access first
        if (is_array($row) && isset($row[$key])) {
            return $row[$key];
        }
        
        // Try object property access
        if (is_object($row) && property_exists($row, $key)) {
            return $row->$key;
        }
        
        // Try common variations
        $variations = [
            strtolower($key),
            snake_case($header),
            camel_case($header),
            str_replace(' ', '_', strtolower($header))
        ];
        
        foreach ($variations as $variation) {
            if (is_array($row) && isset($row[$variation])) {
                return $row[$variation];
            }
            if (is_object($row) && property_exists($row, $variation)) {
                return $row->$variation;
            }
        }
        
        return '';
    }

    /**
     * Format individual values based on column type
     */
    protected function formatValue($value, string $header): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($this->isMoneyColumn($header)) {
            return is_numeric($value) ? (float) $value : 0;
        }

        if ($this->isDateColumn($header)) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }

        if ($this->isPercentageColumn($header)) {
            return is_numeric($value) ? (float) $value / 100 : 0;
        }

        return $value;
    }

    /**
     * Convert header text to potential array key
     */
    protected function headerToKey(string $header): string
    {
        return str_replace(' ', '_', strtolower($header));
    }

    /**
     * Check if column contains monetary values
     */
    protected function isMoneyColumn(string $header): bool
    {
        $moneyKeywords = [
            'amount', 'price', 'cost', 'fee', 'charge', 'balance', 'total',
            'revenue', 'payment', 'owed', 'collected', 'outstanding'
        ];
        
        $lowerHeader = strtolower($header);
        
        foreach ($moneyKeywords as $keyword) {
            if (str_contains($lowerHeader, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if column contains date values
     */
    protected function isDateColumn(string $header): bool
    {
        $dateKeywords = [
            'date', 'created', 'updated', 'completed', 'started', 'time'
        ];
        
        $lowerHeader = strtolower($header);
        
        foreach ($dateKeywords as $keyword) {
            if (str_contains($lowerHeader, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if column contains percentage values
     */
    protected function isPercentageColumn(string $header): bool
    {
        $percentKeywords = [
            'rate', 'percentage', 'percent', 'efficiency', 'completion'
        ];
        
        $lowerHeader = strtolower($header);
        
        foreach ($percentKeywords as $keyword) {
            if (str_contains($lowerHeader, $keyword)) {
                return true;
            }
        }
        
        return false;
    }
}