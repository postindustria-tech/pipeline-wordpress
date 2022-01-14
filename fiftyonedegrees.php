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
    private $fiftone_service;

    /**
     * Constructor.
     * Initializes the instance of this plugin.
     * 
     * @access private
     */
    private function __construct() {
        $this->load_includes();
        $this->fiftone_service = new FiftyoneService();
        $this->setup_constants();		
        $this->setup_wp_actions();
        $this->setup_wp_filters(plugin_basename(__FILE__));
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
        require_once __DIR__ . '/includes/fiftyone-service.php';
        require_once __DIR__ . '/includes/ga-service.php';
        require_once __DIR__ . '/includes/ga-tracking-gtag.php';
        require_once __DIR__ . '/constants.php';
        
        // Include Custom_Dimensions class
        if (!class_exists('Fiftyonedegrees_Custom_Dimensions')) {
            require_once('includes/ga-custom-dimension-class.php');
        }         
    }
          

    function setup_wp_actions() {
        $this->fiftyone_service->setup_wp_actions();
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
            
        // Head actions. These are actions to run before generating an HTML
        // head section.
        add_action(
            'wp_head',
            array($this, 'fiftyonedegrees_ga_add_analytics_code'),
            10);
    }

    function setup_wp_filters() {
        $this->fiftyone_service->setup_wp_filters();
    }
    
    /**
     * Construct a list of Google Analytics custom dimensions and store in
     * an option.
     * 
     * This is called either when GA is enabled, or when the custom dimensions
     * are updated.
     * 
     * @param array $cachedPipeline 51Degrees pipeline
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

    /**
     * Sets up the options needed to add custom dimentions to Google Analytics.
     * 
     * @return void
     */
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

    /**
     * Run if a POST is recieved to update Google Analytics options.
     * 
     * @return void
     */
    function fiftyonedegrees_ga_change_screen() {

        if (isset($_POST[Constants::GA_CHANGE])) {
            
            delete_option("custom_dimension_screen");
            update_option("change_to_authentication_screen", "enabled");
            wp_redirect(get_admin_url() .
                'options-general.php?page=51Degrees&tab=google-analytics' );
        }          
    }   

    /**
     * If a change is made to the Google Analytics token, then update all
     * the relevant options.
     * 
     * @return void
     */
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

    /**
     * If a new Google Analytics token is set in the admin interface, then
     * authenticate it in the Google Analytics service. This will then set
     * the GA_TOKEN option.
     * 
     * @return void
     */
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

    /**
     * If logout from Google Analytics is requested in the admin interface,
     * then remove all existing options relating to Google Analytics.
     * 
     * @return void
     */
    function fiftyonedegrees_ga_logout() {

        if (isset($_POST['ga_log_out'])) {
            
            $this->delete_ga_options();

            wp_redirect(get_admin_url() .
                'options-general.php?page=51Degrees&tab=google-analytics' );
        }
    }

    /**
     * Delete all the options relating to Google Analytics. This will disable
     * the Google Analytics feature.
     */
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