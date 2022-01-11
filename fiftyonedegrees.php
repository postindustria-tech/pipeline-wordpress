<?php
/**
 *  Plugin Name: 51Degrees - Optimize by Device & Location
 *  Plugin URI:  https://51degrees.com/
 *  Description: Optimize your website for a range of devices and personalize your content based on your user’s location.
 *  Version:     1.0.11
 *  Author:      51Degrees
 *  Author URI:  https://51degrees.com/
 *  Text Domain: fiftyonedegrees
 *  License:     EUPL-1.2
 *
 *  This Original Work is copyright of 51 Degrees Mobile Experts Limited.
 *  Copyright 2019 51 Degrees Mobile Experts Limited, 5 Charlotte Close,
 *  Caversham, Reading, Berkshire, United Kingdom RG4 7BY.
 *
 *  This Original Work is licensed under the European Union Public Licence (EUPL) 
 *  v.1.2 and is subject to its terms as set out below.
 *
 *  If a copy of the EUPL was not distributed with this file, You can obtain
 *  one at https://opensource.org/licenses/EUPL-1.2.
 *
 *  The 'Compatible Licences' set out in the Appendix to the EUPL (as may be
 *  amended by the European Commission) shall be deemed incompatible for
 *  the purposes of the Work and the provisions of the compatibility
 *  clause in Article 5 of the EUPL shall not apply.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) { exit; }

/**
 * Main Fiftyonedegrees class.
 * 
 * This is the bulk of the plugin, and where everything else is referenced
 * from.
 * 
 * This class should be used as a singleton, the single instance of this
 * class is returned via the static method get_instance().
 *
 * @since       1.0.0
 */
class Fiftyonedegrees {
    /**
     * @var         Fiftyonedegrees $instance
     * @since       1.0.0
     */
    private static $instance;
    private $ga_service;
    private $gtag_tracking_inst;

    /**
     * Constructor.
     * Initializes the instance of this plugin.
     * 
     * @access private
     */
    private function __construct() {
        $this->load_includes();
        $this->setup_constants();			
        $this->setup_wp_actions();
        $this->setup_wp_filters();
        $this->ga_service = new Fiftyonedegrees_Google_Analytics();
        $this->gtag_tracking_inst = new Fiftyonedegrees_Tracking_Gtag();
    }

    /**
     * Get active instance.
     * 
     * This class is lazily loaded, to the first request to this method
     * will construct the singleton instance.
     *
     * @access      public
     * @since       1.0.0
     * @return      object self::$instance
     */
    public static function get_instance() {

        if (!isset( Fiftyonedegrees::$instance)) {
            self::$instance = new Fiftyonedegrees();
        }
        return self::$instance;
    }

    /**
     * Setup plugin constants.
     * 
     * All constants are global, so must be prefixed with "FIFTYONEDEGREES_".
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function setup_constants() {
        // Setting Global Values.
        define('FIFTYONEDEGREES_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
        define('FIFTYONEDEGREES_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('FIFTYONEDEGREES_PROMPT', 'force');
        define('FIFTYONEDEGREES_ACCESS_TYPE', 'offline');
        define('FIFTYONEDEGREES_RESPONSE_TYPE', 'code');
        define('FIFTYONEDEGREES_CLIENT_ID',
            '296335631462-e36u9us90puu4de17ct7rnklu3j8q63n.apps.googleusercontent.com');
        define(
            'FIFTYONEDEGREES_CLIENT_SECRET',
            'V9lcL-V3SxtGSWWcGsFW9QeI');
        define( 'FIFTYONEDEGREES_REDIRECT', 'urn:ietf:wg:oauth:2.0:oob');
        define(
            'FIFTYONEDEGREES_SCOPE',
            Google_Service_Analytics::ANALYTICS_READONLY .
            " " .  Google_Service_Analytics::ANALYTICS_EDIT);
        define('FIFTYONEDEGREES_CUSTOM_DIMENSION_SCOPE', "HIT");
    }

    /**
     * Include necessary files.
     *
     * @access      private
     * @since       1.0.11
     * @return      void
     */
    private function load_includes() {

        // Load the Google API PHP Client Library.
        include_once __DIR__ . '/lib/vendor/autoload.php';
        require_once __DIR__ . '/includes/pipeline.php';
        require_once __DIR__ . '/includes/ga-service.php';
        require_once __DIR__ . '/includes/ga-tracking-gtag.php';
        require_once __DIR__ . '/constants.php';
        
        // Include Custom_Dimensions class
        if (!class_exists('Fiftyonedegrees_Custom_Dimensions')) {
            require_once('includes/ga-custom-dimension-class.php');
        }         
    }
            
    /**
     * Setup action hooks for the plugin. These hooks are handled
     * by wordpress.
     * 
     * See available actions:
     * https://codex.wordpress.org/Plugin_API/Action_Reference
     *
     * @access      private
     * @since       1.0.11
     * @return      void
     */
    private function setup_wp_actions() {
        
        // The main init action. This runs the processing.
        add_action(
            'init',
            array($this, 'fiftyonedegrees_init'));     

        // Admin actions. These are initialization actions to run before
        // loading the admin interface.
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_register_settings'));
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_ga_authentication'));
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_register_settings'));
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_ga_logout'));
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_ga_set_tracking_id'));
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_ga_update_cd_indices'));
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_ga_change_screen'));
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_ga_enable_tracking'));
        add_action(
            'admin_init',
            array($this, 'fiftyonedegrees_setup_blocks'));
        add_action(
            'admin_init',
            array($this, 'submit_rk_submit_action'));

        // Head actions. These are actions to run before generating an HTML
        // head section.
        add_action(
            'wp_head',
            array($this, 'fiftyonedegrees_ga_add_analytics_code'),
            10);

        // Admin menu actions. These are actions run before the admin
        // menu is written.
        add_action(
            'admin_menu',
            array($this, 'fiftyonedegrees_register_options_page'));

        // Plugin page settings actions.
        add_filter(
            'plugin_action_links_' . plugin_basename(__FILE__),
            array($this, 'fiftyonedegrees_add_plugin_page_settings_link'));

        // Enqueue scripts actions for admin.
        add_action(
            'admin_enqueue_scripts',
            array($this, 'fiftyonedegrees_admin_enqueue_scripts'));
        
        // Add Javascript to the enqueued scripts.
        add_action(
            'wp_enqueue_scripts',
            array($this, 'fiftyonedegrees_javascript'));

        // Add the JSON rest endpoint.
        add_action(
            'rest_api_init',
            array($this, 'fiftyonedegrees_rest_api_init'));     

        // Cache resource key data / pipeline after saving options page
        add_action(
            'update_option',
            array($this, 'fiftyonedegrees_update_option'),
            10,
            10);
    }
    
    /**
     * Setup filter hooks for the plugin. These hooks are handled
     * by wordpress.
     * 
     * See available filters:
     * https://codex.wordpress.org/Plugin_API/Filter_Reference
     *
     * @access      private
     * @since       1.0.11
     * @return      void
     */
    private function setup_wp_filters() {
        
        // Add block filter
        add_filter(
            'render_block',
            array($this, 'fiftyonedegrees_block_filter'),
            10,
            2);
        // Register a custom block category
        add_filter(
            'block_categories_all',
            array($this, 'fiftyonedegrees_block_categories'));
        // Show and hide the conditional-group-block based on properties
        add_filter(
            'render_block',
            array($this, 'fiftyonedegrees_render_block'),
            10,
            2);
    }

    /**
     * Register the settings used by the plugin.
     * 
     * @return void
     */
    function fiftyonedegrees_register_settings() {
        // This is the cached pipeline for the current resource key.
        add_option(Constants::PIPELINE);
        // This is the resource key set by the user to be used to access
        // cloud services.
        add_option(Constants::RESOURCE_KEY);

        // Register the new settings with wordpress.
        register_setting(
            Constants::OPTIONS,
            Constants::RESOURCE_KEY);
    }

    /**
     * Register the JSON endpoint for the pipeline. This is where the
     * JavaScript will callback to instead of the 51Degrees domain.
     * 
     * @return void
     */
    function fiftyonedegrees_rest_api_init() {	
        register_rest_route('fiftyonedegrees/v4', "json", array(
            'methods' => 'POST',
            'args' => array(),
            'callback' => array('Pipeline','getJSON'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Checks if the resource key has been changed, and stores the new one
     * if it has. When the new option has been updated, the pipeline will be
     * rebuilt.
     */
    function submit_rk_submit_action() {

        if (isset($_POST[Constants::RESOURCE_KEY]) &&
            isset($_POST["action"]) &&
            $_POST[Constants::RESOURCE_KEY] !==
            get_option(Constants::RESOURCE_KEY)) {

            $resource_key = sanitize_text_field(wp_unslash(
                $_POST[Constants::RESOURCE_KEY]));
            update_option(Constants::RESOURCE_KEY, $resource_key);

            if (!isset($cachedPipeline['error'])) {
                if (get_option(Constants::ENABLE_GA) &&
                    get_option(Constants::RESOURCE_KEY_UPDATED)) {
                
                    wp_redirect(get_admin_url() .
                        'options-general.php?page=51Degrees&tab=google-analytics');
                    exit();
                }
            }
            else {
                wp_redirect(get_admin_url() .
                    'options-general.php?page=51Degrees&tab=setup' );
                exit();
            }

        }
    }

    /**
     * Construct a list of Google Analytics custom dimensions and store in
     * an option.
     * 
     * This is called either when GA is enabled, or when the custom dimensions
     * are updated.
     * 
     * @param cachedPipeline 51Degrees pipeline
     * @return void
     */
    function populate_selected_dimensions($cachedPipeline) {

        if (!isset($cachedPipeline['error'])) {
                    
            $passed_dimensions = array();
            foreach ($_POST as $key=>$dimension) {
                if (strpos($key, "51D_") !== false) {
                    $key = sanitize_text_field(wp_unslash(
                        str_replace("51D_","", $key)));
                    $passed_dimensions[$key] =
                        sanitize_text_field(wp_unslash($dimension));
                }
            }
            update_option(
                Constants::GA_DIMENSIONS,
                $passed_dimensions);
            update_option(
                Constants::GA_DIMENSIONS_UPDATED,
                true);
        }
    }

    /**
     * If a POST has been made with new Google Analytics custom dimensions,
     * then update them within the plugin.
     * 
     * @return void
     */
    function fiftyonedegrees_ga_update_cd_indices() {

        if (isset($_POST[Constants::GA_UPDATE_DIMENSIONS_POST])) {

            if ("Update Custom Dimension Mappings" ===
                $_POST[Constants::GA_UPDATE_DIMENSIONS_POST]) {
                $this->populate_selected_dimensions(
                    get_option(Constants::PIPELINE));

            }
            wp_redirect(get_admin_url() .
                'options-general.php?page=51Degrees&tab=google-analytics');
        }       
    }

    /**
     * Add Google Analytics JavaScript to the page.
     * 
     * @return void
     */
    function fiftyonedegrees_ga_add_analytics_code() {
        
        echo sprintf(
            esc_html('%1$s'),
            get_option(Constants::GA_JS));			  
    }

    /**
     * If a POST has been made to enable/disable Google Analytics,
     * then enable it and update the custom dimensions within the plugin.
     * 
     * @return void
     */
    function fiftyonedegrees_ga_enable_tracking() {

        if (isset($_POST[Constants::ENABLE_GA])) {

            if ("Enable Google Analytics Tracking" ===
                $_POST[Constants::ENABLE_GA]) {

                $cachedPipeline =
                    get_option(Constants::PIPELINE);
                $this->populate_selected_dimensions($cachedPipeline);

                if (!isset($cachedPipeline['error'])) {

                    $this->execute_ga_tracking_steps();
                }
                
            }
            else {
                delete_option(Constants::GA_JS);
                delete_option(Constants::ENABLE_GA);            
            }

            delete_option(Constants::RESOURCE_KEY_UPDATED);
            delete_option("tracking_id_update_flag");
            delete_option("send_page_view_update_flag");
            delete_option(Constants::GA_DIMENSIONS_UPDATED);
            wp_redirect(get_admin_url() .
                'options-general.php?page=51Degrees&tab=google-analytics');
        }
                            
    }

    function execute_ga_tracking_steps() {

        //Prepare Custom Dimensions
        $customDimensionsTable = new Fiftyonedegrees_Custom_Dimensions();
        $customDimensionsTable->prepare_items();           

        // Get Google analytics Tracking Javascript to be added to the
        // header. 
        $gtag_code = $this->gtag_tracking_inst->output_ga_tracking_code();
        update_option(Constants::GA_JS, $gtag_code);

        // Insert Custom Dimensions in Google Analytics
        $this->ga_service->insert_custom_dimensions();
        
        // Mark tracking is enabled.
        update_option(Constants::ENABLE_GA, "enabled");
    }

    function fiftyonedegrees_ga_change_screen() {

        if (isset($_POST[Constants::GA_CHANGE])) {
            
            delete_option("custom_dimension_screen");
            update_option("change_to_authentication_screen", "enabled");
            wp_redirect(get_admin_url() .
                'options-general.php?page=51Degrees&tab=google-analytics' );
        }          
    }   


    function fiftyonedegrees_ga_set_tracking_id() {         
        if (get_option(Constants::GA_TOKEN)) {
            if (isset($_POST['submit']) &&
                "Save Changes" === $_POST['submit']) {

                delete_option("tracking_id_error");
                update_option("custom_dimension_screen", "enabled");

                if (isset($_POST[Constants::GA_TOKEN]) &&
                    "Select Analytics Property" ===
                    $_POST[Constants::GA_TOKEN]) {

                    update_option("tracking_id_error", true);
                    delete_option("custom_dimension_screen");                        
                }
                else if (isset($_POST[Constants::GA_TOKEN])) {

                    $ga_tracking_id = sanitize_text_field(wp_unslash(
                        $_POST[Constants::GA_TOKEN]));
                    
                    update_option(
                        Constants::GA_TOKEN,
                        $ga_tracking_id);

                    if (isset($_POST[Constants::GA_SEND_PAGE_VIEW]) &&
                        "on" === $_POST[Constants::GA_SEND_PAGE_VIEW]) {
                        update_option(
                            Constants::GA_SEND_PAGE_VIEW,
                            'true');
                        update_option("send_page_view_val", "On");
                    }  
                    else {
                        delete_option(Constants::GA_SEND_PAGE_VIEW);
                        update_option("send_page_view_val", "Off");                      
                    }
                    
                }					
                wp_redirect(get_admin_url() .
                    'options-general.php?page=51Degrees&tab=google-analytics' );
            }     
        }
    }

    function fiftyonedegrees_ga_authentication() {

        if (isset($_POST[Constants::GA_CODE]) &&
            isset($_POST['submit'])) {
            
            $key_google_token = sanitize_text_field(wp_unslash(
                    $_POST[Constants::GA_CODE]));
            $this->ga_service->google_analytics_authenticate(
                $key_google_token);
            delete_option("tracking_id_error");
            wp_redirect(get_admin_url() .
                'options-general.php?page=51Degrees&tab=google-analytics' );
            exit;
        }
    }

    function fiftyonedegrees_ga_logout() {

        if (isset($_POST['ga_log_out'])) {
            
            $this->delete_ga_options();

            wp_redirect(get_admin_url() .
                'options-general.php?page=51Degrees&tab=google-analytics' );
        }
    }

    function delete_ga_options() {

        delete_option(Constants::GA_AUTH_CODE);
        delete_option(Constants::GA_TOKEN);
        delete_option(Constants::GA_PROPERTIES);
        delete_option(Constants::GA_TRACKING_ID);
        delete_option(Constants::GA_ACCOUNT_ID);
        delete_option(Constants::GA_MAX_DIMENSIONS);
        delete_option(Constants::GA_SEND_PAGE_VIEW);
        delete_option(Constants::GA_JS);
        delete_option(Constants::ENABLE_GA);
        delete_option(Constants::GA_ERROR);
        delete_option(Constants::RESOURCE_KEY_UPDATED);
        delete_option(Constants::GA_DIMENSIONS);
        delete_option(Constants::GA_DIMENSIONS_UPDATED);
        delete_option("tracking_id_update_flag");
        delete_option("send_page_view_update_flag");
        delete_option("tracking_id_error");
        delete_option("custom_dimension_screen");
        delete_option("change_to_authentication_screen");
    }
                
    // Add stylesheet for admin pages
    function fiftyonedegrees_admin_enqueue_scripts () {
        wp_enqueue_style(
            Constants::ADMIN_STYLES,
            plugin_dir_url(__FILE__) . "assets/css/fod.css");
        wp_enqueue_style(
            Constants::ADMIN_ICONS,
            "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css");
        wp_enqueue_script(
            Constants::JQUERY,
            plugin_dir_url(__FILE__) . '/assets/js/51D.js',
            array('jquery') #dependencies
        );			
    }

    function fiftyonedegrees_update_option($option, $old_value, $new_value) {

        if ($option === Constants::RESOURCE_KEY) {

            // Remove the cached flowdata from the session.
            if (session_status() === PHP_SESSION_ACTIVE &&
                isset($_SESSION["fiftyonedegrees_data"]) {
                unset($_SESSION["fiftyonedegrees_data"]);
            }

            $pipeline = Pipeline::make_pipeline($new_value);

            if ($pipeline) {
                update_option(
                    Constants::PIPELINE,
                    $pipeline);
            }

            if ($old_value !== $new_value) {
                update_option(Constants::RESOURCE_KEY_UPDATED, true);
                delete_option(Constants::GA_DIMENSIONS);
            }
            else {
                delete_option(Constants::RESOURCE_KEY_UPDATED);
            }
            
        }

        if ($option === Constants::GA_TRACKING_ID &&
            $old_value !== $new_value) {
            update_option("tracking_id_update_flag", true);
            delete_option(Constants::GA_DIMENSIONS);
        }

        if ($option === "send_page_view_val" && $old_value !== $new_value) {
            update_option("send_page_view_update_flag", true);
        }
    }

    function fiftyonedegrees_register_options_page() {
        add_options_page(
            '51Degrees',
            '51Degrees',
            'manage_options',
            '51Degrees',
            array($this, 'fiftyonedegrees_admin_page'));
    }


    function fiftyonedegrees_add_plugin_page_settings_link($links) {
        $links[] = '<a href="' .
            admin_url('options-general.php?page=51Degrees') .
            '">' . __('Settings') . '</a>';
        return $links;
    }

    function fiftyonedegrees_admin_page() {
        include plugin_dir_path(__FILE__) . "/admin.php";
    }

    // Add JavaScript
    function fiftyonedegrees_javascript() {
        wp_enqueue_script(
            "fiftyonedegrees",
            plugin_dir_url(__FILE__) . "assets/js/fod.js");
        wp_add_inline_script(
            "fiftyonedegrees",
            Pipeline::getJavaScript(),
            "before");
    }

    // Add block filter
    function fiftyonedegrees_block_filter($block_content, $block) {
        $content = $block_content;
        $pattern = '/\{Pipeline::get\("[A-Za-z]+",[ ]*"[A-Za-z]+"\)\}/';
        preg_match_all(
            $pattern,
            $block_content,
            $matches,
            PREG_PATTERN_ORDER);

        foreach ($matches as $pattern_matches) {
            foreach ($pattern_matches as $match) {
                $args = str_replace("{Pipeline::get(", "", $match);
                $args = str_replace(")}", "", $args);
                $args = str_replace("\"", "", $args);
                $args = str_replace(" ", "", $args);
                $args = explode(",", $args);

                $value = Pipeline::get($args[0], $args[1]);

                switch (gettype($value)) {
                    case "string":
                        break;
                    case "boolean":
                        if ($value) {
                            $value = "true";
                        } else {
                            $value = "false";
                        }
                        break;
                    case "array":
                        $value = implode(",", $value);
                        break;
                    default:
                        $value = json_encode($value);
                }

                $content = str_replace($match, $value, $content);
            }
        }
        return $content;
    }
            
    function fiftyonedegrees_block_categories($categories) {
        $category_slugs = wp_list_pluck($categories, 'slug');

        return in_array('51Degrees', $category_slugs, true) ?
            $categories :
            array_merge(
                $categories,
                array(
                    array(
                        'slug'  => '51Degrees',
                        'title' => __( '51Degrees', '51D' ),
                        'icon'  => null,
                    ),
                )
        );
    }

    function fiftyonedegrees_init() {

        Pipeline::process();

        if (!Pipeline::$data) {

            //todo error_log() ?
            return;

        }
    }

    function fiftyonedegrees_setup_blocks() {

        wp_register_script(
            'fiftyonedegrees-conditional-group-block',
            plugins_url('conditional-group-block/build/index.js', __FILE__),
            [
                'wp-i18n',
                'wp-element',
                'wp-blocks',
                'wp-components',
                'wp-editor'
            ],
            '1.0.0'
        );

        wp_register_style(
            'fiftyonedegrees-conditional-group-block',
            plugins_url('conditional-group-block/src/editor.css', __FILE__),
            [],
            '1.0.0'
        );
            
        register_block_type(
            'fiftyonedegrees/conditional-group-block',
            [
                'editor_script' => 'fiftyonedegrees-conditional-group-block',
                'style'         => 'fiftyonedegrees-conditional-group-block',
            ]
        );
        
        // Add list of properties to select field in editor interface
        $propertySelect = [
            [
                "label" => "Property", 
                "value" => ""
            ]
        ];
        if (Pipeline::$data) {
            foreach (Pipeline::$data["properties"] as $dataKey =>
                $engineProperties) {
                foreach ($engineProperties as $property) {
                    $propertySelect[] = array(
                        "label" => strtolower(
                            $property["name"] . " (" . $dataKey . ")"),
                        "value" => strtolower(
                            $dataKey . "|" . $property["name"])
                    );
                }
            }

            wp_localize_script(
                "fiftyonedegrees-conditional-group-block",
                'fiftyoneProperties',
                $propertySelect);
        }
    }
            
    function fiftyonedegrees_render_block($block_content, $block) {

        if ('fiftyonedegrees/conditional-group-block' === $block['blockName']) {

            if(isset($block["attrs"]["property"]) &&
                !empty($block["attrs"]["property"]) &&
                isset($block["attrs"]["operator"]) &&
                !empty($block["attrs"]["operator"]) &&
                isset($block["attrs"]["value"]) &&
                !empty($block["attrs"]["value"])){

                $property = $block["attrs"]["property"];

                // Split property and engine by pipe
                $engineDataKey = explode("|", $property)[0];
                $propertyName = explode("|", $property)[1];

                // Get property value
                $value = Pipeline::get($engineDataKey, $propertyName);

                // JSON encode to string if not a string already
                if (!is_string($value)) {

                    $value = json_encode($value);
                }

                $compareValue = $block["attrs"]["value"];

                if (empty($compareValue)) {
                    return;       
                }

                $operator = $block["attrs"]["operator"];

                // Default to not show and then overwrite based on
                // operator rules
                $show = false;

                switch ($operator) {
                    case "is":
                        $show = $value === $compareValue;
                        break;
                    case "not":
                        $show = $value !== $compareValue;
                        break;
                    case "contains":
                        $show = strpos($value, $compareValue) !== false;
                        break;
                }
                
                if (!$show) {
                    return;
                }

            } else {       
                return;       
            }
            
        }

        return $block_content;       
    }
}


// ====================== active - inactive - delete hooks =========================

// Activate Plugin
/**
 * Create instance of fiftyonedegrees class.
 */
function load_fiftyonedegrees() {
	return Fiftyonedegrees::get_instance();
}

add_action('plugin_loaded', 'load_fiftyonedegrees');

register_deactivation_hook(__FILE__, 'fiftyonedegrees_deactivate'); //in-active
register_uninstall_hook(__FILE__, 'fiftyonedegrees_deactivate'); // delete

function fiftyonedegrees_deactivate() {

    Fiftyonedegrees::get_instance()->delete_ga_options();
    delete_option(Constants::RESOURCE_KEY);
    delete_option(Constants::PIPELINE);
}