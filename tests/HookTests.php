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

require_once(__DIR__ . "/../lib/vendor/autoload.php");
require_once(__DIR__ . "/../includes/fiftyone-service.php");

use fiftyone\pipeline\core\PipelineBuilder;
#use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use \Brain\Monkey\Functions;
use \Brain\Monkey\Actions;
use \Brain\Monkey\Filters;
use \Brain\Monkey;


class HookTests extends TestCase {

    private static $pipeline;
	public function set_up() {
        Pipeline::reset();
		parent::set_up();
		Brain\Monkey\setUp();
        $mock_pipeline = (new PipelineBuilder())
            ->add(new TestFlowElement())
            ->build();
        HookTests::$pipeline = array("pipeline" =>  $mock_pipeline, "available_engines" => ["testElement"], "error" => null);
        Functions\when('get_option')->alias(function($arg) {
            if ($arg === 'fiftyonedegrees_resource_key_pipeline') {
                return HookTests::$pipeline;
            }
        });
	}

	public function tear_down() {
		Brain\Monkey\tearDown();
		parent::tear_down();
	}
    
    /**
     * Test that the main init method is added as an init hook.
     */
    public function testInitActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('init', 'FiftyoneService->fiftyonedegrees_init()' ));
    }
    
    /**
     * Test that all the init methods needed for an admin are added as
     * admin_init hooks.
     */
    public function testAdminInitActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('admin_init', 'FiftyoneService->fiftyonedegrees_register_settings()' ));
        self::assertNotFalse(has_action('admin_init', 'FiftyoneService->fiftyonedegrees_setup_blocks()' ));
        self::assertNotFalse(has_action('admin_init', 'FiftyoneService->submit_rk_submit_action()' ));
    }

    /**
     * Test that the options page is added to as an admin_menu hook.
     */
    public function testAdminMenuActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('admin_menu', 'FiftyoneService->fiftyonedegrees_register_options_page()' ));
    }

    /**
     * Test that the pipeline JavaScript is added as a wp_enqueue hook.
     */
    public function testScriptActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('wp_enqueue_scripts', 'FiftyoneService->fiftyonedegrees_javascript()' ));
    }

    /**
     * Test that the JavaScript for admin is added as an admin_enqueue_scripts hook.
     */
    public function testAdminScriptActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('admin_enqueue_scripts', 'FiftyoneService->fiftyonedegrees_admin_enqueue_scripts()' ));
    }

    /**
     * Test that the function for the REST API endpoint is added as a rest_api_init hook.
     */
    public function testRestApiActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('rest_api_init', 'FiftyoneService->fiftyonedegrees_rest_api_init()' ));
    }

    /**
     * Test that our update option function is hooked to update_option.
     */
    public function testUpdateOptionActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('update_option', 'FiftyoneService->fiftyonedegrees_update_option()' ));
    }

    /**
     * Test that the rendering functions are added to the render_block hook.
     */
    public function testRenderBlockFilters() {
        (new FiftyoneService())->setup_wp_filters("");
        self::assertEquals(10, has_filter('render_block', 'FiftyoneService->fiftyonedegrees_block_filter()' ));
        self::assertEquals(10, has_filter('render_block', 'FiftyoneService->fiftyonedegrees_render_block()' ));
    }

    /**
     * Test that the block categories filter is added to the block_categories_all hook.
     */
    public function testBlockCategoryFilters() {
        (new FiftyoneService())->setup_wp_filters("");
        self::assertEquals(10, has_filter('block_categories_all', 'FiftyoneService->fiftyonedegrees_block_categories()' ));
    }
    
    /**
     * Test that the actions links filter is added top the correctly named
     * hook.
     */
    public function testActionLinkFilters() {
        $pluginName = "fiftyone";
        (new FiftyoneService())->setup_wp_filters($pluginName);
        self::assertEquals(10, has_filter('plugin_action_links_' . $pluginName, 'FiftyoneService->fiftyonedegrees_add_plugin_page_settings_link()' ));
    }

    /**
     * Test that the blocks, and their required scripts and styles,
     * are registered.
     */
    public function testRegisterBlocks() {
        Pipeline::process();
        Functions\when('plugins_url')
            ->returnArg();
        Functions\expect('wp_register_script')
            ->once()
            ->with(
                'fiftyonedegrees-conditional-group-block',
                'conditional-group-block/build/index.js',
                Mockery::any(),
                Mockery::any());
        
        Functions\expect('wp_register_style')
            ->once()
            ->with(
                'fiftyonedegrees-conditional-group-block',
                'conditional-group-block/src/editor.css',
                Mockery::any(),
                Mockery::any());
        
        Functions\expect('register_block_type')
            ->once()
            ->with(
                'fiftyonedegrees/conditional-group-block',
                Mockery::any());
                
        Functions\expect('wp_localize_script')
            ->once()
            ->with('fiftyonedegrees-conditional-group-block',
                'fiftyoneProperties',
                Mockery::any());
    
        (new FiftyoneService())->fiftyonedegrees_setup_blocks();

        // We are asserting via the expect, so tell PHPUnit not
        // to worry.
        $this->assertTrue(true);
    }

    /**
     * Test that the any scripts are correctly added.
     */
    function testAddedJavaScript() {
        Functions\when('plugin_dir_url')
            ->justReturn('root/');
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with('fiftyonedegrees', 'root/assets/js/fod.js');

        Functions\expect('wp_add_inline_script')
            ->once()
            ->with("fiftyonedegrees", Mockery::any(), "before");

        (new FiftyoneService())->fiftyonedegrees_javascript();
       
        // We are asserting via the expect, so tell PHPUnit not
        // to worry.
        $this->assertTrue(true);
    }
}
?>