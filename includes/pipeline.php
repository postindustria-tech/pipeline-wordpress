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

use fiftyone\pipeline\core\PipelineBuilder;
use fiftyone\pipeline\cloudrequestengine\CloudRequestEngine;
use fiftyone\pipeline\cloudrequestengine\CloudEngine;
use fiftyone\pipeline\core\Utils;

class Pipeline
{
    public static $data = null;

   /**
	* Makes a pipeline from a resource key that 
	* can be serialized to the database
	* @param resourceKey Resource Key
	* @return Array containing pipeline and engines
	*/
    public static function make_pipeline($resourceKey) {

		// Get App Context from the URL.
        $url = get_site_url();
		$appContext = Pipeline::getAppContext($url);
		
        // Prepare PipelineBuilder and add the JavaScript settings for the JavaScriptBuilder,
		// in this case an endpoint to call back to to retrieve additional
		// properties populated by client side evidence
		// this ?json endpoint is used later to serve results from a special
		// json engine automatically included in the pipeline		
        $builder = new PipelineBuilder([
            "javascriptBuilderSettings" => [
                "endpoint" => $appContext . "/wp-json/fiftyonedegrees/v4/json",
                "host" => isset($_SERVER['HTTP_HOST']) ? sanitize_text_field( $_SERVER['HTTP_HOST'] ) : $url,
                "protocol" => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https" : "http",
				"minify" => false
            ]
        ]);

        $error = null;
        try {
            $cloud = new CloudRequestEngine(array("resourceKey" => $resourceKey));
        }
        catch (Exception $e) {
            $error = $e->getMessage();
            return array("pipeline" =>  null, "available_engines" => null, "error" => $error);
        }
        

        // Get engines available with th resource key
        $engines = array_keys($cloud->flowElementProperties);

        // Add CloudRequestEngine to the pipeline.
        $builder->add($cloud);

        // Add all the engines, accessible via provided
        // resourceKey, to the pipeline
        foreach ($engines as $engine) {
            $cloudEngine = new CloudEngine();
            $cloudEngine->dataKey = $engine;
            $builder->add($cloudEngine);
        }

        // Build the pipeline
        $pipeline = $builder->build();

        return array("pipeline" =>  $pipeline, "available_engines" => $engines, "error" => $error);

    }

    /**
     * process function sets the evidence from web request in flowData and
	 * runs the process function on each attached FlowElement
     * @return array, containing FlowData, properties and errors.
     */
    public static function process()
    {
        if (Pipeline::$data === null) {
    
            require_once dirname(__DIR__) . "/lib/vendor/autoload.php";
           
            $cachedPipeline = get_option('fiftyonedegrees_resource_key_pipeline');

            if(isset($cachedPipeline["error"]) || !$cachedPipeline){
                return;
            }

            // Get pipeline and available engines from cache
            $pipeline = $cachedPipeline["pipeline"];
            $engines = $cachedPipeline["available_engines"];

	        // Create flowData object in the pipeline.   
            $flowData = $pipeline->createFlowData();

            // Set evidence from web request.
            $flowData->evidence->setFromWebRequest();

            // Process flowData with evidence supplied
            $flowData->process();

			// Some browsers require that extra HTTP headers are explicitly
			// requested. So set whatever headers are required by the browser in
			// order to return the evidence needed by the pipeline.
			// More info on this can be found at
			// https://51degrees.com/blog/user-agent-client-hints
			Utils::setResponseHeader($flowData);

            // Get properties for each engine from pipeline.
            $properties = array();
            foreach ($engines as $engine) {
                $properties[$engine] = $pipeline->getElement($engine)->getProperties();
            }

            Pipeline::$data = array("flowData" => $flowData, "properties" => $properties, "errors" => $flowData->errors);
        }
    }

    /**
     * Retrieves property by engine and property key
     * @param string FlowElementDataKey
	 * @param string Property Key
     * @return String Property Value
     */	
    public static function get($engine, $key) {

        $result =  Pipeline::$data;
        if(isset($result["errors"]) && count($result["errors"])) {
            error_log("Errors processing Flow Data" . $result["errors"]);
            return;
        }

        $flowData = $result["flowData"];

        try {
            if($flowData->{$engine}->{$key}->hasValue) {
                return $flowData->{$engine}->{$key}->value;
            }
            else {
                error_log($flowData->{$engine}->{$key}->noValueMessage);
                return null;
            }
        }
        catch (\Exception $e) {
            error_log($e->getMessage());
            return null;
        }

    }

    /**
     * Retrieves flowData properties JSON object
     * @return JSON Object
     */	
    public static function getJSON() {

        $result =  Pipeline::$data;
        if(isset($result["errors"]) && count($result["errors"])) {
            error_log("Errors processing Flow Data" . $result["errors"]);
            return;
        }

        $flowData = $result["flowData"];
        try {
            return $flowData->jsonbundler->json;
        }
        catch (\Exception $e) {
            error_log($e->getMessage());
            return;
        }       
    }

    /**
     * Retrieves properties list by category
	 * @param string Category
     * @return Array Properties List
     */	
    public static function getCategory($category)
    {
        $result =  Pipeline::$data;
        if(isset($result["errors"]) && count($result["errors"])) {
            error_log("Errors processing Flow Data" . $result["errors"]);
            return;
        }

        $flowData = $result["flowData"];

        $categoryResults = $flowData->getWhere("category", $category);
        $output = array();

        foreach ($categoryResults as $key => $property) {
            $value = null;

            if($property->hasValue) {
                $value = $property->value;
            }
            else {
                //error_log($property->noValueMessage);
                $value = null;
            }
            $output[$key] = $value;
        }
        return $output;
    }

    /**
     * Gets client side javascript from FlowData
     * @param Javascript
     */
    public static function getJavaScript() {
        $result =  Pipeline::$data;
        if(isset($result["errors"]) && count($result["errors"])) {
            error_log("Errors processing Flow Data" . $result["errors"]);
            return;
        }

        $flowData = $result["flowData"];
        
        try {
            return $flowData->javascriptbuilder->javascript;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return "";
        }
    }
 
    /**
     * Gets AppContext from the URL
     * @param URL
     */
    public static function getAppContext($url) {
        $urlParts = explode("/", str_replace("https://", "", str_replace("http://", "", $url)));
		if(count($urlParts) > 1) {
			$appContext = "/" . end($urlParts);
		}
		else { 
		    $appContext = "";
		}
		return $appContext;
    }

}
