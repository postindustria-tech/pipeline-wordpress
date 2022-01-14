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
require(__DIR__ . "/../includes/fiftyone-service.php");

use fiftyone\pipeline\core\PipelineBuilder;
use PHPUnit\Framework\TestCase;
use \Brain\Monkey\Functions;
use \Brain\Monkey\Actions;
use \Brain\Monkey\Filters;
use \Brain\Monkey;


class HookTests extends TestCase {

	public function setUp(): void {
		parent::setUp();
		Brain\Monkey\setUp();
	}

	public function tearDown(): void {
		Brain\Monkey\tearDown();
		parent::tearDown();
	}
    
    public function testInitActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('init', 'FiftyoneService->fiftyonedegrees_init()' ));
    }
    
    public function testAdminInitActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('admin_init', 'FiftyoneService->fiftyonedegrees_register_settings()' ));
        self::assertNotFalse(has_action('admin_init', 'FiftyoneService->fiftyonedegrees_setup_blocks()' ));
        self::assertNotFalse(has_action('admin_init', 'FiftyoneService->submit_rk_submit_action()' ));
    }

    public function testAdminMenuActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('admin_menu', 'FiftyoneService->fiftyonedegrees_register_options_page()' ));
    }

    public function testScriptActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('wp_enqueue_scripts', 'FiftyoneService->fiftyonedegrees_javascript()' ));
    }

    public function testAdminScriptActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('admin_enqueue_scripts', 'FiftyoneService->fiftyonedegrees_admin_enqueue_scripts()' ));
    }

    public function testRestApiActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('rest_api_init', 'FiftyoneService->fiftyonedegrees_rest_api_init()' ));
    }

    public function testUpdateOptionActions() {
        (new FiftyoneService())->setup_wp_actions();
        self::assertNotFalse(has_action('update_option', 'FiftyoneService->fiftyonedegrees_update_option()' ));
        self::assertNotFalse(has_action('update_option', 'FiftyoneService->fiftyonedegrees_update_option()' ));
    }

    public function testRenderBlockFilters() {
        (new FiftyoneService())->setup_wp_filters("");
        self::assertTrue(has_filter('render_block', 'FiftyoneService->fiftyonedegrees_block_filter()' ));
        self::assertTrue(has_filter('render_block', 'FiftyoneService->fiftyonedegrees_render_block()' ));
    }

    public function testBlockCategoryFilters() {
        (new FiftyoneService())->setup_wp_filters("");
        self::assertTrue(has_filter('block_categories_all', 'FiftyoneService->fiftyonedegrees_block_categories()' ));
    }
    
    public function testActionLinkFilters() {
        $pluginName = "fiftyone";
        (new FiftyoneService())->setup_wp_filters($pluginName);
        self::assertTrue(has_filter('plugin_action_links_' . $pluginName, 'FiftyoneService->fiftyonedegrees_add_plugin_page_settings_link()' ));
    }
}
?>