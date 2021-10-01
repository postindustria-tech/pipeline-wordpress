<?php
/*
    This Original Work is copyright of 51 Degrees Mobile Experts Limited.
    Copyright 2019 51 Degrees Mobile Experts Limited, 5 Charlotte Close,
    Caversham, Reading, Berkshire, United Kingdom RG4 7BY.

    This Original Work is licensed under the European Union Public Licence (EUPL) 
    v.1.2 and is subject to its terms as set out below.

    If a copy of the EUPL was not distributed with this file, You can obtain
    one at https://opensource.org/licenses/EUPL-1.2.

    The 'Compatible Licences' set out in the Appendix to the EUPL (as may be
    amended by the European Commission) shall be deemed incompatible for
    the purposes of the Work and the provisions of the compatibility
    clause in Article 5 of the EUPL shall not apply.
*/

require(__DIR__ . "/../lib/vendor/autoload.php");
require(__DIR__ . "/../includes/ga-tracking-gtag.php");

use PHPUnit\Framework\TestCase;
use \Brain\Monkey\Functions;

class GaTrackingGtagTests extends TestCase {

    // Data Provider for testCustomDimensionsFromProperties
	public function provider_testCustomDimensionsFromProperties()
    {
        return array(
            array(array(array("property_name" => "testproperty1", "custom_dimension_index" => 1, "custom_dimension_name" => "51D.device.testproperty1",
            "custom_dimension_ga_index" => 1, "custom_dimension_datakey" => "device"),
            array("property_name" => "testproperty2", "custom_dimension_index" => 5, "custom_dimension_name" => "51D.location.testproperty2",
            "custom_dimension_ga_index" => 5, "custom_dimension_datakey" => "location")), 
            array("dimension1" => "testproperty1", "dimension5" => "testproperty2"), 
            array("testproperty1" => "data.device.testproperty1", "testproperty2" => "data.location.testproperty2"), true),
    
            array(array(array("property_name" => "testproperty1", "custom_dimension_index" => 10, "custom_dimension_name" => "51D.device.testproperty1",
            "custom_dimension_ga_index" => 10, "custom_dimension_datakey" => "device")), 
            array("dimension10" => "testproperty1"), 
            array("testproperty1" => "data.device.testproperty1"), false)
        );
    }
    /**
     *  Tests Customs Dimensions are correctly converted from properties.
     *  @dataProvider provider_testCustomDimensionsFromProperties
     */
    public function testCustomDimensionsFromProperties($cust_dims_map, $expected_dims_map, $expected_events_map, $delayed_evidence) {
        
        Functions\expect('get_option')->once()->with('fiftyonedegrees_ga_cust_dims_map')->andReturn($cust_dims_map);

        $ga_tracking_gtag = new Fiftyonedegrees_Tracking_Gtag();
        $result = $ga_tracking_gtag->get_properties_as_custom_dimensions();
        
        $this->assertEquals($expected_dims_map, $result["dimensions_map"]);
        $this->assertEquals($expected_events_map, $result["events_map"]);
        $this->assertEquals($delayed_evidence, $result["delayed_evidence"]);

    }

    /**
     *  Tests javascript for location engine with send page view.
     */
    public function testCustomDimensionPopulationInJS() {

        // inputs to the test case 
        $cust_dims_map = array(array("property_name" => "testproperty1", "custom_dimension_index" => 1, "custom_dimension_name" => "51D.device.testproperty1",
        "custom_dimension_ga_index" => 1, "custom_dimension_datakey" => "device"),
        array("property_name" => "testproperty2", "custom_dimension_index" => 5, "custom_dimension_name" => "51D.location.testproperty2",
        "custom_dimension_ga_index" => 5, "custom_dimension_datakey" => "location"));        
        // Mock fiftonedegrees_tracking_id and fiftyonedegrees_send_page_view values.
        Functions\expect('get_option')->once()->with('fiftyonedegrees_ga_tracking_id')->andReturn('test-123456789-0');
        Functions\expect('get_option')->once()->with('fiftyonedegrees_ga_send_page_view')->andReturn(true);

        // Mocking get_properties_as_custom_dimensions function output.
        $ga_dimensions_map = array("dimension1" => "testproperty1", "dimension5" => "testproperty2");
        $ga_events_map =  array("testproperty1" => "data.device.testproperty1", "testproperty2" => "data.location.testproperty2");        
        $cust_dims = array ("dimensions_map" => $ga_dimensions_map, "events_map" => $ga_events_map, "delayed_evidence" => true);
        $mock = Mockery::mock('Fiftyonedegrees_Tracking_Gtag')->makePartial();
        $mock->shouldReceive('get_properties_as_custom_dimensions')->andReturn($cust_dims);        
        $ga_custom_dims = $mock->get_properties_as_custom_dimensions();

        // Get actual gtag javascript
        $result = $mock->output_gtag_code();
        $trimmed_result = preg_replace("/\s+/", "", $result);

        // Get expected javascript
        $expected_output = file_get_contents("tests/outputs/ga-51d-tracking-location.js");
        $trimmed_expected_output = preg_replace("/\s+/", "", $expected_output);

        $this->assertEquals($trimmed_result, $trimmed_expected_output);

    }

    /**
     *  Tests javascript for already tagged device engine.
     */
    public function testAlreadyTaggedJavascript() {

        // inputs to the test case 
        $cust_dims_map = array("property_name" => "testproperty1", "custom_dimension_index" => 10, "custom_dimension_name" => "51D.device.testproperty1",
        "custom_dimension_ga_index" => 10, "custom_dimension_datakey" => "device");
        // Mock fiftonedegrees_tracking_id and fiftyonedegrees_send_page_view values.
        Functions\expect('get_option')->once()->with('fiftyonedegrees_ga_tracking_id')->andReturn('test-123456789-0');
        
        // Mocking get_properties_as_custom_dimensions function output.
        $ga_dimensions_map = array("dimension10" => "testproperty1");
        $ga_events_map =  array("testproperty1" => "data.device.testproperty1");        
        $cust_dims = array ("dimensions_map" => $ga_dimensions_map, "events_map" => $ga_events_map, "delayed_evidence" => false);
        $mock = Mockery::mock('Fiftyonedegrees_Tracking_Gtag')->makePartial();
        $mock->shouldReceive('get_properties_as_custom_dimensions')->andReturn($cust_dims);        
        $ga_custom_dims = $mock->get_properties_as_custom_dimensions();

        // Get actual gtag javascript
        $result = $mock->output_gtag_code_tagged_property();
        $trimmed_result = preg_replace("/\s+/", "", $result);

        // Get expected javascript
        $expected_output = file_get_contents("tests/outputs/ga-integration-tracking.js");
        $trimmed_expected_output = preg_replace("/\s+/", "", $expected_output);

        $this->assertEquals($trimmed_result, $trimmed_expected_output);

    }
}