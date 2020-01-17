<?php
   /*
   Plugin Name: 51Degrees device detection
   Plugin URI: https://51degrees.com/
   description: Device Detection
   Version: 0.1
   Author: 51Degrees
   Author URI: https://51degrees.com/
   License: --
   */
?>

<?php

use fiftyone\pipeline\devicedetection\deviceDetectionPipelineBuilder;

function fiftyonedegrees_register_settings() {

    add_option( 'fiftyonedegrees_resource_key', '');
    register_setting( 'fiftyonedegrees_options', 'fiftyonedegrees_resource_key');

    add_option( 'fiftyonedegrees_license_key', '');
    register_setting( 'fiftyonedegrees_options', 'fiftyonedegrees_license_key');

}

add_action( 'admin_init', 'fiftyonedegrees_register_settings' );

function fiftyonedegrees_register_options_page() {
    add_options_page('51Degrees', '51Degrees', 'manage_options', '51Degrees',             'fiftyonedegrees_admin_page');
}

add_action('admin_menu', 'fiftyonedegrees_register_options_page');

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'fiftyonedegrees_add_plugin_page_settings_link');

function fiftyonedegrees_add_plugin_page_settings_link( $links ) {
    
	$links[] = '<a href="' .
		admin_url( 'options-general.php?page=51Degrees' ) .
		'">' . __('Settings') . '</a>';
	return $links;
}

function fiftyonedegrees_admin_page() {

    include plugin_dir_path(__FILE__) . "/admin.php";

}

class fiftyonedegrees {

    static function process(){

        static $cache = null;

        if($cache === null) {

            require_once plugin_dir_path(__FILE__) . "library/vendor/autoload.php";

            if(!get_option("fiftyonedegrees_resource_key")){

                return;

            }

            $builder = new deviceDetectionPipelineBuilder(array(
                "resourceKey" => get_option("fiftyonedegrees_resource_key"),
                "licenseKey" => get_option("fiftyonedegrees_license_key")
            ));

            $pipeline = $builder->build();

            $flowData = $pipeline->createFlowData();

            $flowData->evidence->setFromWebRequest();

            $flowData->process();

            $result = array("flowData" => $flowData, "properties" => $pipeline->getElement("device")->getProperties());

            $cache = $result; 

            return $result;

        } else {

            return $cache;
            
        }

    }

    static function get($key){

        $flowData = fiftyonedegrees::process()["flowData"];

        try {

            return $flowData->device->{$key}->value;

        } catch(Exception $e) {

           return null;

        }

    }

    static function getCategory($category){

        $flowData = fiftyonedegrees::process()["flowData"];

        $results = $flowData->getWhere("category", $category);

        $output = array();

        foreach ($results as $key => $property){

            $output[$key] = fiftyonedegrees::get($key);

        }

        return $output;

    }

    static function javascript($echo = true){

        $flowData = fiftyonedegrees::process()["flowData"];

        try {

            $js = $flowData->javascriptbundler->javascript;

            if($echo){

                echo "<script>" . $js . "</script>";

            } else {

                return $js;

            }


        } catch(Exception $e) {

           return "";

        }

    }

}

