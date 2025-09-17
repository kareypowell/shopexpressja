<?php

namespace Tests\Unit;

use App\Http\Livewire\Admin\BackupHistory;
use Tests\TestCase;

class BackupHistoryComponentSimpleTest extends TestCase
{
    /**
     * Test that the BackupHistory component class exists and has required methods
     */
    public function test_backup_history_component_class_exists()
    {
        $this->assertTrue(class_exists(BackupHistory::class));
        
        $component = new BackupHistory();
        
        // Test that required methods exist
        $this->assertTrue(method_exists($component, 'render'));
        $this->assertTrue(method_exists($component, 'sortBy'));
        $this->assertTrue(method_exists($component, 'generateDownloadLink'));
        $this->assertTrue(method_exists($component, 'generateBatchDownloadLinks'));
        $this->assertTrue(method_exists($component, 'deleteBackup'));
        $this->assertTrue(method_exists($component, 'toggleFilters'));
        $this->assertTrue(method_exists($component, 'clearFilters'));
    }

    /**
     * Test that the component has the required properties
     */
    public function test_backup_history_component_has_required_properties()
    {
        $component = new BackupHistory();
        
        // Test that required properties exist
        $this->assertTrue(property_exists($component, 'search'));
        $this->assertTrue(property_exists($component, 'typeFilter'));
        $this->assertTrue(property_exists($component, 'statusFilter'));
        $this->assertTrue(property_exists($component, 'dateFrom'));
        $this->assertTrue(property_exists($component, 'dateTo'));
        $this->assertTrue(property_exists($component, 'sortField'));
        $this->assertTrue(property_exists($component, 'sortDirection'));
        $this->assertTrue(property_exists($component, 'selectedBackups'));
        $this->assertTrue(property_exists($component, 'showFilters'));
    }

    /**
     * Test that the component has the correct default values
     */
    public function test_backup_history_component_default_values()
    {
        $component = new BackupHistory();
        
        $this->assertEquals('', $component->search);
        $this->assertEquals('', $component->typeFilter);
        $this->assertEquals('', $component->statusFilter);
        $this->assertEquals('created_at', $component->sortField);
        $this->assertEquals('desc', $component->sortDirection);
        $this->assertEquals([], $component->selectedBackups);
        $this->assertFalse($component->showFilters);
    }

    /**
     * Test that the formatBytes method works correctly
     */
    public function test_format_bytes_method()
    {
        $component = new BackupHistory();
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        // Test various byte sizes
        $this->assertEquals('0 B', $method->invoke($component, 0));
        $this->assertEquals('1024 B', $method->invoke($component, 1024));
        $this->assertEquals('1 KB', $method->invoke($component, 1025));
        $this->assertEquals('1 MB', $method->invoke($component, 1024 * 1024 + 1));
        $this->assertEquals('500 B', $method->invoke($component, 500));
        $this->assertEquals('1.5 KB', $method->invoke($component, 1536));
    }
}