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
require(__DIR__ . "/../includes/pipeline.php");
require(__DIR__ . "/TestFlowElement.php");

use fiftyone\pipeline\core\PipelineBuilder;
use PHPUnit\Framework\TestCase;
use \Brain\Monkey\Functions;

class PipelineTests extends TestCase {

    // Data Provider for testGetAppContext
	public function provider_testGetAppContext()
    {
        return array(
        array("http://localhost/testsite", "/testsite"),
        array("https://test.domain.com", ""),
        );
    }

    /**
     * Test to check appContext from URLs
     * @dataProvider provider_testGetAppContext
     */
    public function testGetAppContext($url, $expectedValue) {

        $result = Pipeline::getAppContext($url);
        $this->assertEquals($expectedValue, $result);
    }

    public function testMakePipeline_ValidResourceKey() {

        //A fake get_site_url() that always return 'http://localhost/testsite'
        Functions\when('get_site_url')->justReturn('http://localhost/testsite');

        $resourceKey = $_ENV["RESOURCEKEY"];
        if ($resourceKey === "!!YOUR_RESOURCE_KEY!!") {
            $this->fail("You need to create a resource key at " .
            "https://configure.51degrees.com and paste it into the " .
            "phpunit.xml config file, " .
            "replacing !!YOUR_RESOURCE_KEY!!.");
        }

        $result = Pipeline::make_pipeline($resourceKey);

        $this->assertEquals(get_class($result["pipeline"]), "fiftyone\pipeline\core\Pipeline");
        $this->assertEquals('device', $result["available_engines"][0]);
    }

    public function testMakePipeline_InValidResourceKey() {

        //A fake get_site_url() that always return 'http://localhost/testsite'
        Functions\when('get_site_url')->justReturn('http://localhost/testsite');

        $resourceKey = "XXXXXXXXXXXXXX";
        $result = Pipeline::make_pipeline($resourceKey);

        $this->assertEquals($result["error"], "Cloud request engine properties list request returned 'XXXXXXXXXXXXXX' is not a valid resource key.");

    }

    public function testProcess() {

        //A fake get_site_url() that always return 'http://localhost/testsite'
        Functions\when('get_site_url')->justReturn('http://localhost/testsite');

        $resourceKey = $_ENV["RESOURCEKEY"];
        if ($resourceKey === "!!YOUR_RESOURCE_KEY!!") {
            $this->fail("You need to create a resource key at " .
            "https://configure.51degrees.com and paste it into the " .
            "phpunit.xml config file, " .
            "replacing !!YOUR_RESOURCE_KEY!!.");
        }

        $pipeline = Pipeline::make_pipeline($resourceKey);
        Functions\expect('get_option')->once()->with('fiftyonedegrees_resource_key_pipeline')->andReturn($pipeline);

        $result = Pipeline::process();
        $this->assertEquals(get_class($result["flowData"]), "fiftyone\pipeline\core\FlowData");
        $this->assertTrue(isset($result["properties"]));
        $this->assertTrue(count($result["errors"]) == 0);
        
    }

    public function testGet() {

        // Create a tmpfile to write errors to.
        $capture = tmpfile();
        $saved = ini_set('error_log', stream_get_meta_data($capture)['uri']);

        $mock_pipeline   = (new PipelineBuilder())
                            ->add(new TestFlowElement())
                            ->build();
        $pipeline = array("pipeline" =>  $mock_pipeline, "available_engines" => ["testElement"], "error" => null);

        Functions\expect('get_option')->times(5)->with('fiftyonedegrees_resource_key_pipeline')->andReturn($pipeline);
        Functions\when('plugin_dir_path')->justReturn(getcwd(). "/");

        // Tests Pipeline::get Function.
        $result1 = Pipeline::get("testElement", "availableProperty");
        $this->assertEquals("Value", $result1);

        $result2 = Pipeline::get("testElement", "noValueProperty");
        $this->assertTrue(strpos(stream_get_contents($capture), "Property is not available.") !== false);
        $this->assertNull($result2);

        $result3 = Pipeline::get("testElement", "notAvailableProperty");
        $this->assertTrue(strpos(stream_get_contents($capture), "Trying to get property") !== false);
        $this->assertNull($result3);

        $result4 = Pipeline::get("notAvailableElement", "availableProperty");
        $this->assertTrue(strpos(stream_get_contents($capture), "There is no element data for 'notAvailableElement' against this flow data. Available element data keys are: 'testElement,jsonbundler,javascriptbuilder,set-headers") !== false);
        $this->assertNull($result4);

        // Tests Pipeline::getCategory Function.
        $expectedResult = array('availableProperty' => "Value", 'noValueProperty' => null);
        $categoryResult = Pipeline::getCategory("testCategory");
        $this->assertEquals($expectedResult, $categoryResult);

    }
}
    