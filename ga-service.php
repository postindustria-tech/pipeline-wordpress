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

// Setting Global Values.
define( 'FIFTYONEDEGREES_PROMPT', 'force' );
define( 'FIFTYONEDEGREES_ACCESS_TYPE', 'offline' );
define( 'FIFTYONEDEGREES_RESPONSE_TYPE', 'code' );
define( 'FIFTYONEDEGREES_CLIENT_ID', '296335631462-e36u9us90puu4de17ct7rnklu3j8q63n.apps.googleusercontent.com');
define( 'FIFTYONEDEGREES_CLIENT_SECRET', 'V9lcL-V3SxtGSWWcGsFW9QeI' );
define( 'FIFTYONEDEGREES_REDIRECT', 'urn:ietf:wg:oauth:2.0:oob' );
define( 'FIFTYONEDEGREES_SCOPE', Google_Service_Analytics::ANALYTICS_READONLY . " " .  Google_Service_Analytics::ANALYTICS_EDIT);
define( 'FIFTYONEDEGREES_CUSTOM_DIMENSION_SCOPE', "HIT");

use Google\Service\Analytics\CustomDimension;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Analytics Service class 
 *
 * @since       1.0.0
 * 
 * @package Fiftyonedegrees
 * @author  Fatima Tariq
 */
class Fiftyonedegrees_Google_Analytics {

    public function google_analytics_authenticate( $key_google_token ) {
        
        try {

            update_option( 'fiftyonedegrees_ga_auth_code', $key_google_token );
            $client = $this->authenticate();

            if ( $client ) { 
              
                $service = $this->get_google_analytics_service( $client );
                $this->get_analytics_properties_list($service);
                return true; 

            } else {
                echo "Could not authenticate with the user.";
            }
    
        } catch (Exception $e) {
    
            echo $e->getMessage();
        }
        return false; 
    }
    
    public function authenticate() {

        $client = new Google_Client();
        $client->setApprovalPrompt( FIFTYONEDEGREES_PROMPT );
        $client->setAccessType( FIFTYONEDEGREES_ACCESS_TYPE );
        $client->setClientId( FIFTYONEDEGREES_CLIENT_ID );
        $client->setClientSecret( FIFTYONEDEGREES_CLIENT_SECRET );
        $client->setRedirectUri( FIFTYONEDEGREES_REDIRECT );	
        $client->setScopes( Google_Service_Analytics::ANALYTICS_READONLY );
        
        $ga_google_authtoken = get_option( 'fiftyonedegrees_ga_access_token' );
    
        if ( ! empty( $ga_google_authtoken ) ) {
    
            $client->setAccessToken( $ga_google_authtoken );
        } else {
    
            $auth_code = get_option( 'fiftyonedegrees_ga_auth_code' );
    
            if ( empty( $auth_code ) ) { 
                return false; 
            }
    
            try {
    
                $access_token = $client->authenticate( $auth_code );
            } catch ( Analytify_Google_Auth_Exception $e ) {

                echo  $e->getMessage();
                return false;

            } catch ( Exception $e ) {

                echo  $e->getMessage();
                return false;

            }
    
            if ( $access_token ) {
    
                $client->setAccessToken( $access_token );
    
                update_option( 'fiftyonedegrees_ga_access_token', $access_token );
                update_option( 'fiftyonedegrees_ga_auth_date', date( 'l jS F Y h:i:s A' ) . date_default_timezone_get() );
    
            } else {
    
                return false;
            }
        }

        return $client;
    }
    
    public function get_google_analytics_service ( $client ) {
        try {
            
            // Create an authorized analytics service object.
            $service = new Google_Service_Analytics( $client );
             
        } catch ( Google_Service_Exception $e ) {
            
            echo $e->getMessage();

        } catch ( Exception $e ) {
            
            echo $e->getMessage();

        }

        return $service;		
    }

    /**
     * Retrieves all properties for the authorized user.
     */
    public function get_analytics_properties_list($analytics_service) {
  
        if (!get_option( 'fiftyonedegrees_ga_access_token' )) {
            echo "You must authenticate to access your Analytics Account.";
            return;
        }
    
        $properties = get_option( 'fiftyonedegrees_ga_properties_list' );
    
        if ( empty( $properties ) ) {
    
            try {
                // Get the list of accounts for the authorized user.
                $properties = $analytics_service->management_webproperties->listManagementWebproperties('~all');
                $propertiesList = array();
                if (count($properties->getItems()) > 0) {
                    foreach ($properties->getItems() as $property) {
                        $propertyId = $property->getId();
                        $propertyName = $property->getName();
                        $property = array();
                        $property["id"] = $propertyId;
                        $property["name"] = $propertyName . " (" . $propertyId . ") "; 
                        array_push($propertiesList, $property);           
                    }
                }
                else {
                    echo 'No Properties found for this user.';
                    return;
                }  
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        update_option( 'fiftyonedegrees_ga_properties_list' , $propertiesList );
    
        return $propertiesList;
    }

    /**
     * Retrieves Maximum custom dimension index for the authorized user.
     */
    public function get_account_id( $analytics_service, $trackingId ) {

        if( !empty( $trackingId ) ) {

            try {
                // Get the list of accounts and web properties.
                $accounts = $analytics_service->management_accountSummaries->listManagementAccountSummaries();
                foreach ($accounts->getItems() as $account) {
                    $accountId = $account->getId();
                    foreach ($account->getWebProperties() as $property) {
                        if ( $property->getId() === $trackingId ){
                            return $accountId;
                        }
                    }
                }
            }
            catch (apiServiceException $e) {
                echo 'There was an Analytics API service error '
                      . $e->getCode() . ':' . $e->getMessage();
                return "";
              
            } catch (apiException $e) {
                echo 'There was a general API error '
                    . $e->getCode() . ':' . $e->getMessage();
                return "";
            }
        }
        return ""; 
    }

    /**
     * Retrieves Maximum custom dimension index for the authorized user.
     */
    public function get_custom_dimensions() {

        $trackingId = get_option("fiftyonedegrees_ga_tracking_id");
        $maxCustomDimIndex = get_option("fiftyonedegrees_ga_max_cust_dim_index");
        $client = $this->authenticate();

        if ( $client ) {  

            $service = $this->get_google_analytics_service( $client ); 

            // Get accountId from tracking Id
            $accountId = $this->get_account_id( $service, $trackingId );
            update_option("fiftyonedegrees_ga_account_id", $accountId);

            // Get the list of custom dimensions for the web property.
            $customDimensions = $service->management_customDimensions->listManagementCustomDimensions($accountId, $trackingId);
            
            // Create a map with custom dimensions name and indices.
            $custom_dimensions_map = array();
            foreach ($customDimensions->getItems() as $customDimension) {
                $customDimensionName = $customDimension->getName();
                $customDimensionIndex = $customDimension->getIndex();
                $custom_dimensions_map[$customDimensionName] = $customDimensionIndex;
            }

            // Get Maximum Custom Dimension Index
            $maxCustomDimIndex = count($customDimensions->getItems());
            update_option("fiftyonedegrees_ga_max_cust_dim_index", $maxCustomDimIndex);       
    
        } 
        else {
            echo "User is not authenticated.";
        }

        return array( "cust_dims_map" => $custom_dimensions_map, "max_cust_dim_index" => $maxCustomDimIndex );
    }

    /**
     * Inserts Custom Dimension into analytics account for the authorized user.
     */
    public function insert_custom_dimensions() {

        $accountId = get_option("fiftyonedegrees_ga_account_id");
        $trackingId = get_option("fiftyonedegrees_ga_tracking_id");
        $cust_dim_map = get_option("fiftyonedegrees_ga_cust_dims_map");

        $client = $this->authenticate();

        if ( $client ) {  

            $service = $this->get_google_analytics_service( $client );

            foreach ( $cust_dim_map as $dimension ) {

                $custDimName = $dimension["custom_dimension_name"];
                $custDimGAIndex = $dimension["custom_dimension_ga_index"];
                $custDimIndex = $dimension["custom_dimension_index"];

                if( $custDimGAIndex === -1 ) {

                    $customDimension = new CustomDimension();
                    $customDimension->setName($custDimName);
                    $customDimension->setIndex($custDimIndex);
                    $customDimension->setScope(FIFTYONEDEGREES_CUSTOM_DIMENSION_SCOPE);
                    $customDimension->setActive(true);

                    try {
                        // Insert Custom Dimension in Google Analytics
                        $result = $service->management_customDimensions->insert($accountId, $trackingId, $customDimension);
                    } catch (Exception $e) {
                    echo $e->getMessage();
                    }
                
                }
            }     
        }
        else {
            echo "Could not authenticate the user.";
        }   
    }
}
    
