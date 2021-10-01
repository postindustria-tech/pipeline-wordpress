<!--
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
-->

<!-- Add JQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript">var enabledButton = "<?php echo get_option("fiftyonedegrees_ga_enable_tracking"); ?>";</script>
<script type="text/javascript" src= '<?php echo FIFTYONEDEGREES_PLUGIN_URL .  "assets/js/51D.js"; ?>;'></script>

<?php

 if ( !get_option( 'fiftyonedegrees_ga_access_token' ) && empty(get_option( 'fiftyonedegrees_ga_access_token' ))) { 
            echo '<span class="fod-pipeline-status error">Please Authenticate with Google Analytics first.</span>';
 }  else { 
     
        if ( get_option('fiftyonedegrees_resource_key_pipeline')['error']) {
            echo '<p></p><span class="fod-pipeline-status error">Provided resourceKey does not contain any Custom Dimensions. Please enter a valid resource key. </span>';        
        }
        else if ( get_option( "fiftyonedegrees_ga_error" ) ) {
            echo '<p></p><span class="fod-pipeline-status warn">' . get_option("fiftyonedegrees_ga_error") . '</span>';
            delete_option( "fiftyonedegrees_ga_error" );
        }
        else if ( get_option("fiftyonedegrees_ga_enable_tracking") ) {
            if ( get_option("fiftyonedegrees_resource_key_updated") || get_option("tracking_id_update_flag") || get_option("send_page_view_update_flag") || get_option("fiftyonedegrees_passed_dimensions_updated") ) {          

                // Include Fiftyonedegrees class
                if (!class_exists('Fiftyonedegrees')) {
                    require_once('fiftyonedegrees.php');
                } 

                $instance = Fiftyonedegrees::get_instance();
                $instance->execute_ga_tracking_steps();

                if (get_option("fiftyonedegrees_resource_key_updated")) {
                    echo '<p></p><span class="fod-pipeline-status good">Google Analytics Tracking is enabled for the Properties available in the new resource key. </span>';      
                    delete_option( "fiftyonedegrees_resource_key_updated" );
                } else if ( get_option("fiftyonedegrees_passed_dimensions_updated") ) {
                    echo '<p></p><span class="fod-pipeline-status good">Google Analytics Custom Dimensions mapping has been updated.</span>';                    
                    delete_option( "fiftyonedegrees_passed_dimensions_updated" );
                }                    
                else {
                    echo '<p></p><span class="fod-pipeline-status good">Google Analytics Tracking is enabled for new Google Analytics Property Settings.</span>';
                    delete_option( "tracking_id_update_flag" );
                    delete_option( "send_page_view_update_flag" );
                }
            }
            else {
                echo '<p></p><span class="fod-pipeline-status good">Google Analytics Custom Dimensions data collection is now enabled for you. </span>';
            }            
        }
     
    if ( !get_option('fiftyonedegrees_resource_key_pipeline')['error']) {
    ?>
            
        <form method="post" action="options.php">	
        <table style="height: 50%">
        <tbody>
            <tr>
            
               <td><p>Following properties are available with the provided resourcekey. Please Click <b>Enable Google Analytics Tracking</b> to send them as Custom Dimensions to <b><?php echo get_option("fiftyonedegrees_ga_tracking_id"); ?></b> Google Analytics Property or <b>Go Back</b> to change. </p></td>
               <td><button type="submit" class="button-primary" name="fiftyonedegrees_ga_change_settings" ><span style="font-size:16px;">&laquo;</span> Go Back</i></button></td>
            
            </tr>
            </tbody>
        </table>
        </form>
        <?php
            
            // Include Custom_Dimensions class
            if (!class_exists('Fiftyonedegrees_Custom_Dimensions')) {
                require_once('includes/ga-custom-dimension-class.php');
            }    
            //Prepare Custom Dimensions Table
            $customDimensionsTable = new Fiftyonedegrees_Custom_Dimensions();
            $customDimensionsTable->prepare_items();
        ?>

        <form method="post" action="options.php">
            <?php $customDimensionsTable->display();?>

            <table style="width: 100%">
            <tbody>
            <tr>         
            <?php if ( "enabled" !== get_option("fiftyonedegrees_ga_enable_tracking")) { ?>
                <td style="width: 90%"><input type="submit" class="button-primary" value="Enable Google Analytics Tracking" name="fiftyonedegrees_ga_enable_tracking" /></td>           
            <?php } else { ?>
                <td style="width: 90%"><input type="submit" class="button-primary" value="Disable Google Analytics Tracking" name="fiftyonedegrees_ga_enable_tracking" /></td>
            <?php } ?> 
                <td><input type="submit" class="button-primary" value="Update Custom Dimension Mappings" name="fiftyonedegrees_ga_update_cd_indices" />  </td>
            </tr>
            </tbody>
            </table>
        </form>

    <?php } } ?>

