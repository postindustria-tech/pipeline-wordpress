<?php 

 if ( !get_option( 'fiftyonedegrees_ga_access_token' ) && empty(get_option( 'fiftyonedegrees_ga_access_token' ))) { 
            echo '<span class="fod-pipeline-status error">Please Authenticate with Google Analytics first.</span>';
 }  else { 
     
        if (get_option('fiftyonedegrees_resource_key_pipeline')['error']) {
            echo '<p></p><span class="fod-pipeline-status error">Provided resourceKey does not contain Custom Dimensions. Please enter a valid resource key. </span>';        
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
                    echo '<p></p><span class="fod-pipeline-status good">Google Analytics Tracking is enabled with the Properties available in the new resource key. </span>';      
                    delete_option( "fiftyonedegrees_resource_key_updated" );
                } else if ( get_option("fiftyonedegrees_passed_dimensions_updated") ) {
                    echo '<p></p><span class="fod-pipeline-status good">Google Analytics Custom Dimension mappings updated.</span>';                    
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

        <!-- Add Edit icon library -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <script src='https://kit.fontawesome.com/a076d05399.js' crossorigin='anonymous'></script>
            
        <form method="post" action="options.php">	
        <table style="height: 50%">
        <tbody>
            <tr>
                <td><p>Following properties are available with the provided resourcekey. Please Click <b>Enable Google Analytics Tracking</b> to send them as Custom Dimensions to <b><?php echo get_option("fiftyonedegrees_ga_tracking_id"); ?></b> Google Analytics Property or click <b>Edit</b> to change. </p></td>
                <td><button type="submit" class="btn" name="fiftyonedegrees_ga_change_settings" ><i class="far fa-edit" style='font-size:25px'></i></button></td>
            </tr>
            </tbody>
        </table>
        </form>
        <?php
            
            // Include Custom_Dimensions class
            if (!class_exists('Fiftyonedegrees_Custom_Dimensions')) {
                require_once('ga-custom-dimension-class.php');
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

    <?php } } 
    
    ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>

    window.addEventListener( "load", function() {
    
        $("input[name='fiftyonedegrees_ga_update_cd_indices']").hide();
        var selected_values = getSelectedListValues();
        localStorage.removeItem('selectedValues');
        localStorage.setItem('selectedValues', selected_values);       
    });
    
    $(document).ready(function(){

        $('.51DPropertiesList select').change(function() {
          
            const selected_values_str = localStorage.getItem('selectedValues');
            var selected_values = selected_values_str.split(',');

            var curr_selected_values = getSelectedListValues();
            var enabledButton = "<?php echo get_option("fiftyonedegrees_ga_enable_tracking"); ?>";

            console.log(enabledButton);
            if(enabledButton === "enabled" && arrayMatch(curr_selected_values, selected_values) === false) {
                $("input[name='fiftyonedegrees_ga_update_cd_indices']").show();
            }else {
                $("input[name='fiftyonedegrees_ga_update_cd_indices']").hide();
            }

        }).trigger('change');
    });

    var getSelectedListValues = function() {

        var selected_values = new Array();
        var selected_arr =  document.getElementsByTagName('select');
        for(k=0;k< selected_arr.length;k++)
        {
            sel = selected_arr[k];
            if(sel.name.indexOf('51D_') === 0){
                selected_values.push(sel.value);
            }
        }

        return selected_values;
        
    }

    var arrayMatch = function (arr1, arr2) {

        // Check if the arrays are the same length
        if (arr1.length !== arr2.length) return false;

        // Check if all items exist and are in the same order
        for (var i = 0; i < arr1.length; i++) {

            if (arr1[i] !== arr2[i]) return false;
        }

        return true;
    };
</script>