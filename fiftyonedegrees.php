<?php
/**
 *  Plugin Name: 51Degrees
 *  Plugin URI:  https://51degrees.com/
 *  Description: 51Degrees WordPress plugin makes use of the 51Degrees Pipeline API to deliver Data Intelligence services.
 *  Version:     0.9
 *  Author:      51Degrees
 *  Author URI:  https://51degrees.com/
 *  Text Domain: fiftyonedegrees
 *  License:     EUPL
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
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Main Fiftyonedegrees class
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
		 * [__construct description]
		 */
		function __construct() {
            $this->includes();
			$this->setup_constants();			
			$this->hooks();
            $this->ga_service = new Fiftyonedegrees_Google_Analytics();
            $this->gtag_tracking_inst = new Fiftyonedegrees_Tracking_Gtag();
        }

		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      object self::$instance
		 */
		public static function get_instance() {

			if ( !isset(Fiftyonedegrees::$instance)) {
				self::$instance = new Fiftyonedegrees();
			}
			return self::$instance;
		}

		/**
		 * Setup plugin constants
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function setup_constants() {
			// Setting Global Values.
            define( 'FIFTYONEDEGREES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
            define( 'FIFTYONEDEGREES_PLUGIN_URL', plugin_dir_url(__FILE__) );
            define( 'FIFTYONEDEGREES_PROMPT', 'force' );
            define( 'FIFTYONEDEGREES_ACCESS_TYPE', 'offline' );
            define( 'FIFTYONEDEGREES_RESPONSE_TYPE', 'code' );
            define( 'FIFTYONEDEGREES_CLIENT_ID', '296335631462-e36u9us90puu4de17ct7rnklu3j8q63n.apps.googleusercontent.com');
            define( 'FIFTYONEDEGREES_CLIENT_SECRET', 'V9lcL-V3SxtGSWWcGsFW9QeI' );
            define( 'FIFTYONEDEGREES_REDIRECT', 'urn:ietf:wg:oauth:2.0:oob' );
            define( 'FIFTYONEDEGREES_SCOPE', Google_Service_Analytics::ANALYTICS_READONLY . " " .  Google_Service_Analytics::ANALYTICS_EDIT);
            define( 'FIFTYONEDEGREES_CUSTOM_DIMENSION_SCOPE', "HIT");
        }

		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {

            // Load the Google API PHP Client Library.
            include_once __DIR__ . '/lib/vendor/autoload.php';
            require_once __DIR__ . '/includes/pipeline.php';
            require_once __DIR__ . '/includes/ga-service.php';
            require_once __DIR__ . '/includes/ga-tracking-gtag.php';
            
            // Include Custom_Dimensions class
            if (!class_exists('Fiftyonedegrees_Custom_Dimensions')) {
                require_once('includes/ga-custom-dimension-class.php');
            }         
		}
             
		/**
		 * Run action and filter hooks
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function hooks() {
            
            add_action('admin_init', array( $this, 'fiftyonedegrees_register_settings' ) );
            add_action('admin_init', array( $this, 'fiftyonedegrees_ga_authentication' ) );
            add_action('admin_init', array( $this, 'fiftyonedegrees_register_settings' ) );
            add_action('admin_init', array( $this, 'fiftyonedegrees_ga_logout' ) );
            add_action('admin_init', array( $this, 'fiftyonedegrees_ga_set_tracking_id' ) );
            add_action('admin_init', array( $this, 'fiftyonedegrees_ga_update_cd_indices' ) );
            add_action('admin_init', array( $this, 'fiftyonedegrees_ga_change_screen' ) );
            add_action('admin_init', array( $this, 'fiftyonedegrees_ga_enable_tracking' ) );
            
            add_action('admin_init', array( $this, 'submit_rk_submit_action' ));

            add_action( 'wp_head', array( $this, 'fiftyonedegrees_ga_add_analytics_code' ), 10 );

            add_action('admin_menu', array( $this, 'fiftyonedegrees_register_options_page' ));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'fiftyonedegrees_add_plugin_page_settings_link' ));

            add_action('admin_enqueue_scripts', array( $this, 'fiftyonedegrees_admin_enqueue_scripts' ));
            
            // Add Javascript
            add_action('wp_enqueue_scripts', array( $this, 'fiftyonedegrees_javascript' ));
            add_action('rest_api_init', array( $this, 'fiftyonedegrees_rest_api_init' ));
            add_action('init', array( $this, 'fiftyonedegrees_init' ));          
            
            // Cache resource key data / pipeline after saving options page
            add_action( 'update_option', array( $this, 'fiftyonedegrees_update_option' ), 10, 10);

            // Add block filter
            add_filter( 'render_block', array( $this, 'fiftyonedegrees_block_filter' ), 10, 2 );
            // Register a custom block category
            add_filter( 'block_categories_all', array( $this, 'fiftyonedegrees_block_categories' ));
            // Show and hide the conditional-group-block based on properties
            add_filter( 'render_block', array( $this, 'fiftyonedegrees_render_block' ), 10, 2 );

		}
        
        function fiftyonedegrees_register_settings()
        {
            add_option("fiftyonedegrees_resource_key_pipeline");
            add_option("fiftyonedegrees_resource_key");
            register_setting('fiftyonedegrees_options', 'fiftyonedegrees_resource_key');
        }

        function fiftyonedegrees_rest_api_init() {	
            register_rest_route('fiftyonedegrees/v4', "json", array(
            'methods' => 'POST',
            'args' => array(),
            'callback' => array('Pipeline','getJSON'),
            'permission_callback' => '__return_true'
            ));
        }

        function submit_rk_submit_action() {

            if(isset($_POST["fiftyonedegrees_resource_key"]) && isset($_POST["action"])) {

                $resource_key = $_POST["fiftyonedegrees_resource_key"];
                update_option("fiftyonedegrees_resource_key", $resource_key);
                update_option("fiftyonedegrees_resource_key", $resource_key);

                if(!isset($cachedPipeline['error'])) {
                    if ( get_option("fiftyonedegrees_ga_enable_tracking") && get_option("fiftyonedegrees_resource_key_updated")) {                    
                    
                        wp_redirect(  get_admin_url() . 'options-general.php?page=51Degrees&tab=google-analytics' );
                        exit();
                    }
                }
                else {
                    wp_redirect(  get_admin_url() . 'options-general.php?page=51Degrees&tab=setup' );
                    exit();
                }

            }
        }

        function populate_selected_dimensions() {

            if(!isset($cachedPipeline['error'])) {
                        
                $passed_dimensions = array();
                foreach($_POST as $key=>$dimension) {
                    if( strpos($key, "51D_") !== false) {
                        $key = str_replace("51D_","", $key);
                        $passed_dimensions[$key] = $dimension;
                    }
                }
                update_option("fiftyonedegrees_passed_dimensions", $passed_dimensions);
                update_option("fiftyonedegrees_passed_dimensions_updated", true);
            }
        }

        function fiftyonedegrees_ga_update_cd_indices() {
            if ( isset($_POST["fiftyonedegrees_ga_update_cd_indices"])){

                if ("Update Custom Dimension Mappings" === $_POST["fiftyonedegrees_ga_update_cd_indices"]) {

                    $cachedPipeline = get_option('fiftyonedegrees_resource_key_pipeline');

                    $this->populate_selected_dimensions();

                }
                wp_redirect(  get_admin_url() . 'options-general.php?page=51Degrees&tab=google-analytics' );
            }       
        }

        function fiftyonedegrees_ga_add_analytics_code() {

            echo get_option("fiftyonedegrees_ga_tracking_javascript");
        }

        function fiftyonedegrees_ga_enable_tracking() {

            if ( isset($_POST["fiftyonedegrees_ga_enable_tracking"])){

                if ("Enable Google Analytics Tracking" === $_POST["fiftyonedegrees_ga_enable_tracking"]) {

                    $cachedPipeline = get_option('fiftyonedegrees_resource_key_pipeline');
                    $this->populate_selected_dimensions();

                    if(!isset($cachedPipeline['error'])){

                        $this->execute_ga_tracking_steps();                    
                    }
                    
                }
                else {
                    delete_option( "fiftyonedegrees_ga_tracking_javascript" );
                    delete_option( "fiftyonedegrees_ga_enable_tracking" );            
                }

                delete_option( "fiftyonedegrees_resource_key_updated" );
                delete_option( "tracking_id_update_flag" );
                delete_option( "send_page_view_update_flag" );
                delete_option( "fiftyonedegrees_passed_dimensions_updated" );
                wp_redirect(  get_admin_url() . 'options-general.php?page=51Degrees&tab=google-analytics' );
            }
                              
        }

        function execute_ga_tracking_steps() {
 
            //Prepare Custom Dimensions
            $customDimensionsTable = new Fiftyonedegrees_Custom_Dimensions();
            $customDimensionsTable->prepare_items();           

            // Get Google analytics Tracking Javascript to be added to the header. 
            $gtag_code = $this->gtag_tracking_inst->output_ga_tracking_code();
            update_option("fiftyonedegrees_ga_tracking_javascript", $gtag_code);

            // Insert Custom Dimensions in Google Analytics
            $this->ga_service->insert_custom_dimensions();
            
            // Mark tracking is enabled.
            update_option("fiftyonedegrees_ga_enable_tracking", "enabled");
        }

        function fiftyonedegrees_ga_change_screen() {

            if ( isset( $_POST['fiftyonedegrees_ga_change_settings'] ) /*&& "Change" === $_POST['fiftyonedegrees_ga_change_settings']*/) {
               
                delete_option("custom_dimension_screen");
                update_option("change_to_authentication_screen", "enabled");
                wp_redirect(  get_admin_url() . 'options-general.php?page=51Degrees&tab=google-analytics' );
            }          
        }   


        function fiftyonedegrees_ga_set_tracking_id() {         
            if (get_option("fiftyonedegrees_ga_access_token")) {
                if ( isset( $_POST['submit'] ) && "Save Changes" === $_POST['submit']) {

                    delete_option("tracking_id_error");
                    update_option("custom_dimension_screen", "enabled");

                    if ( isset( $_POST['fiftyonedegrees_ga_tracking_id']) && "Select Analytics Property" === $_POST['fiftyonedegrees_ga_tracking_id']) {
                        update_option("tracking_id_error", true);
                        delete_option("custom_dimension_screen");                        
                    } else if (isset( $_POST['fiftyonedegrees_ga_tracking_id'])) {

                        $ga_tracking_id = sanitize_text_field( wp_unslash( $_POST['fiftyonedegrees_ga_tracking_id'] ) );
                        
                        update_option("fiftyonedegrees_ga_tracking_id", $ga_tracking_id );                   
    
                        if (isset( $_POST['fiftyonedegrees_ga_send_page_view'] ) && "on" === $_POST['fiftyonedegrees_ga_send_page_view'] ) {
                            update_option("fiftyonedegrees_ga_send_page_view", 'true' );
                            update_option("send_page_view_val", "On");
                        }  
                        else {
                            delete_option("fiftyonedegrees_ga_send_page_view");
                            update_option("send_page_view_val", "Off");                      
                        }
                       
                    }					
                    wp_redirect(  get_admin_url() . 'options-general.php?page=51Degrees&tab=google-analytics' );
                }     
            }
        }

        function fiftyonedegrees_ga_authentication() {
        

            if ( isset( $_POST['fiftyonedegrees_ga_code'] ) && isset($_POST['submit']) ) {
              
                $key_google_token = sanitize_text_field( wp_unslash( $_POST['fiftyonedegrees_ga_code'] ) );
                $this->ga_service->google_analytics_authenticate( $key_google_token );
                delete_option( "tracking_id_error" );
                wp_redirect(  get_admin_url() . 'options-general.php?page=51Degrees&tab=google-analytics' );
                exit;
            }
                       
        }
        
        function fiftyonedegrees_ga_logout() {

            if ( isset( $_POST['ga_log_out'] ) ) {
				
				$this->delete_ga_options();

                wp_redirect(  get_admin_url() . 'options-general.php?page=51Degrees&tab=google-analytics' );

            }
            
        }
		
        function delete_ga_options() {
			
                delete_option( "fiftyonedegrees_ga_auth_code" );
                delete_option( "fiftyonedegrees_ga_access_token" );
                delete_option( "fiftyonedegrees_ga_properties_list" );
                delete_option( "fiftyonedegrees_ga_tracking_id" );
                delete_option( "fiftyonedegrees_ga_account_id" );
                delete_option( "fiftyonedegrees_ga_max_cust_dim_index" );
                delete_option( "fiftyonedegrees_ga_send_page_view" );
                delete_option( "fiftyonedegrees_ga_tracking_javascript" );
                delete_option( "fiftyonedegrees_ga_enable_tracking" );
                delete_option( "fiftyonedegrees_ga_error" );
                delete_option( "fiftyonedegrees_ga_auth_code" ); 
                delete_option( "fiftyonedegrees_resource_key_updated" );
                delete_option( "tracking_id_update_flag" );
                delete_option( "send_page_view_update_flag" );
                delete_option( "tracking_id_error" );
                delete_option( "custom_dimension_screen" );
                delete_option( "change_to_authentication_screen" );
                delete_option( "fiftyonedegrees_passed_dimensions" );
                delete_option( "fiftyonedegrees_passed_dimensions_updated" );				
		}
                    
        // Add stylesheet for admin pages
        function fiftyonedegrees_admin_enqueue_scripts (){
            wp_enqueue_style('fiftyonedegrees_admin_styles', plugin_dir_url(__FILE__) . "assets/css/fod.css");
        }
        
        function fiftyonedegrees_update_option($option, $old_value, $new_value){
        
            if($option === "fiftyonedegrees_resource_key"){
        
                $pipeline = Pipeline::make_pipeline($new_value);
        
                if($pipeline){
                    update_option("fiftyonedegrees_resource_key_pipeline", $pipeline);
                }
        
                if ($old_value !== $new_value) {
                    update_option("fiftyonedegrees_resource_key_updated", true);
					delete_option( "fiftyonedegrees_passed_dimensions" );                   
                }
                else {
                    delete_option( "fiftyonedegrees_resource_key_updated" );
                }
                
            }

            if ($option === "fiftyonedegrees_ga_tracking_id" && $old_value !== $new_value) {
                update_option( "tracking_id_update_flag", true );
                delete_option( "fiftyonedegrees_passed_dimensions" );
            }

            if ($option === "send_page_view_val" && $old_value !==  $new_value) {
                update_option( "send_page_view_update_flag", true );
            }
        }
        
        function fiftyonedegrees_register_options_page()
        {
            add_options_page('51Degrees', '51Degrees', 'manage_options', '51Degrees', array( $this, 'fiftyonedegrees_admin_page'));
        }
        
        
        function fiftyonedegrees_add_plugin_page_settings_link($links)
        {
            $links[] = '<a href="' .
                admin_url('options-general.php?page=51Degrees') .
                '">' . __('Settings') . '</a>';
            return $links;
        }
        
        function fiftyonedegrees_admin_page()
        {
            include plugin_dir_path(__FILE__) . "/admin.php";
        }
        
        // Add JavaScript
        function fiftyonedegrees_javascript()
        {
            wp_enqueue_script("fiftyonedegrees", plugin_dir_url(__FILE__) . "assets/js/fod.js");
            wp_add_inline_script("fiftyonedegrees", Pipeline::getJavaScript(), "before");
        }
        
        // Add block filter
        function fiftyonedegrees_block_filter( $block_content, $block ) {
            $content = $block_content;
            $pattern = '/\{Pipeline::get\("[A-Za-z]+",[ ]*"[A-Za-z]+"\)\}/';
            preg_match_all($pattern, $block_content, $matches, PREG_PATTERN_ORDER);
        
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
                            if($value){
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
               
        function fiftyonedegrees_block_categories( $categories ) {
            $category_slugs = wp_list_pluck( $categories, 'slug' );
            return in_array( '51Degrees', $category_slugs, true ) ? $categories : array_merge(
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
            
            // Remove Magic Quotes from auto escaped data.
            $_GET       = array_map('stripslashes_deep', $_GET);
            $_POST      = array_map('stripslashes_deep', $_POST);	
            $_COOKIE    = array_map('stripslashes_deep', $_COOKIE);
            $_SERVER    = array_map('stripslashes_deep', $_SERVER);
            $_REQUEST   = array_map('stripslashes_deep', $_REQUEST);
            
            wp_register_script(
                'fiftyonedegrees-conditional-group-block',
                plugins_url( 'conditional-group-block/build/index.js' , __FILE__ ),
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
                plugins_url( 'conditional-group-block/src/editor.css' , __FILE__ ),
                [],
                '1.0.0'
            );
                
            register_block_type( 'fiftyonedegrees/conditional-group-block',
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
        
            $data = Pipeline::process();
        
            if(!$data){
        
                return;
        
            }
             
            foreach ($data["properties"] as $dataKey => $engineProperties) {
                foreach ($engineProperties as $property){
                    $propertySelect[] = array(
                        "label" => strtolower($property["name"] . " (" . $dataKey . ")"),
                        "value" => strtolower($dataKey . "|" . $property["name"])
                    );
                }
            }
            
            wp_localize_script( "fiftyonedegrees-conditional-group-block", 'fiftyoneProperties', $propertySelect);
        
        }
               
        function fiftyonedegrees_render_block( $block_content, $block ) {
        
            if ('fiftyonedegrees/conditional-group-block' === $block['blockName']) {
        
                if(isset($block["attrs"]["property"]) && !empty($block["attrs"]["property"]) && isset($block["attrs"]["operator"]) && !empty($block["attrs"]["operator"]) && isset($block["attrs"]["value"]) && !empty($block["attrs"]["value"])){
        
                    $property = $block["attrs"]["property"];
        
                    // Split property and engine by pipe
        
                    $engineDataKey = explode("|", $property)[0];
                    $propertyName = explode("|", $property)[1];
        
                    // Get property value
        
                    $value = Pipeline::get($engineDataKey, $propertyName);
        
                    // JSON encode to string if not a string already
        
                    if(!is_string($value)){
        
                        $value = json_encode($value);
        
                    }
        
                    $compareValue = $block["attrs"]["value"];
        
                    if(empty($compareValue)){       
                        return;       
                    }
        
                    $operator = $block["attrs"]["operator"];
        
                    // Default to not show and then overwrite based on operator rules
        
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
                    
                    if(!$show){                   
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

add_action( 'plugin_loaded', 'load_fiftyonedegrees' );

register_deactivation_hook( __FILE__, 'fiftyonedegrees_deactivate' ); //in-active
register_uninstall_hook( __FILE__, 'fiftyonedegrees_deactivate' ); // delete

function fiftyonedegrees_deactivate() {

    Fiftyonedegrees::get_instance()->delete_ga_options();
    delete_option( "fiftyonedegrees_resource_key" );
    delete_option( "fiftyonedegrees_resource_key_pipeline" );

}