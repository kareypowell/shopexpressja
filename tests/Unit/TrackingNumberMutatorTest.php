<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\PreAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrackingNumberMutatorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function package_tracking_number_is_converted_to_uppercase()
    {
        $package = new Package();
        $package->tracking_number = 'abc123def';
        
        $this->assertEquals('ABC123DEF', $package->tracking_number);
    }

    /** @test */
    public function package_tracking_number_trims_whitespace()
    {
        $package = new Package();
        $package->tracking_number = '  abc123def  ';
        
        $this->assertEquals('ABC123DEF', $package->tracking_number);
    }

    /** @test */
    public function package_warehouse_receipt_no_is_converted_to_uppercase()
    {
        $package = new Package();
        $package->warehouse_receipt_no = 'wr123abc';
        
        $this->assertEquals('WR123ABC', $package->warehouse_receipt_no);
    }

    /** @test */
    public function package_warehouse_receipt_no_trims_whitespace()
    {
        $package = new Package();
        $package->warehouse_receipt_no = '  wr123abc  ';
        
        $this->assertEquals('WR123ABC', $package->warehouse_receipt_no);
    }

    /** @test */
    public function consolidated_package_tracking_number_is_converted_to_uppercase()
    {
        $consolidatedPackage = new ConsolidatedPackage();
        $consolidatedPackage->consolidated_tracking_number = 'cons123abc';
        
        $this->assertEquals('CONS123ABC', $consolidatedPackage->consolidated_tracking_number);
    }

    /** @test */
    public function consolidated_package_tracking_number_trims_whitespace()
    {
        $consolidatedPackage = new ConsolidatedPackage();
        $consolidatedPackage->consolidated_tracking_number = '  cons123abc  ';
        
        $this->assertEquals('CONS123ABC', $consolidatedPackage->consolidated_tracking_number);
    }

    /** @test */
    public function pre_alert_tracking_number_is_converted_to_uppercase()
    {
        $preAlert = new PreAlert();
        $preAlert->tracking_number = 'pa123def';
        
        $this->assertEquals('PA123DEF', $preAlert->tracking_number);
    }

    /** @test */
    public function pre_alert_tracking_number_trims_whitespace()
    {
        $preAlert = new PreAlert();
        $preAlert->tracking_number = '  pa123def  ';
        
        $this->assertEquals('PA123DEF', $preAlert->tracking_number);
    }

    /** @test */
    public function null_tracking_numbers_remain_null()
    {
        $package = new Package();
        $package->tracking_number = null;
        
        $this->assertNull($package->tracking_number);
        
        $consolidatedPackage = new ConsolidatedPackage();
        $consolidatedPackage->consolidated_tracking_number = null;
        
        $this->assertNull($consolidatedPackage->consolidated_tracking_number);
        
        $preAlert = new PreAlert();
        $preAlert->tracking_number = null;
        
        $this->assertNull($preAlert->tracking_number);
    }

    /** @test */
    public function empty_string_tracking_numbers_remain_empty()
    {
        $package = new Package();
        $package->tracking_number = '';
        
        $this->assertEquals('', $package->tracking_number);
        
        $consolidatedPackage = new ConsolidatedPackage();
        $consolidatedPackage->consolidated_tracking_number = '';
        
        $this->assertEquals('', $consolidatedPackage->consolidated_tracking_number);
        
        $preAlert = new PreAlert();
        $preAlert->tracking_number = '';
        
        $this->assertEquals('', $preAlert->tracking_number);
    }
}