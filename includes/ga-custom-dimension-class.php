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

// Custom Dimensions table

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/screen.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fiftyonedegrees_Custom_Dimensions extends WP_List_Table
{       
    public function get_columns()
    {
        return $columns= array(
           'property_name'=>__('Property Name'),
           'custom_dimension_name'=>__('Custom Dimension Name'),
           'custom_dimension_index'=>__('Index')
        );
    }

    public function get_sortable_columns()
    {
        return $sortable = array(
           'property_name'=> array('property_name',true),
           'custom_dimension_index'=> array('custom_dimension_index',true)
        );
    }

    public function prepare_items()
    {
        $result = Pipeline::process();

        if(!$result){

            return;

        }

        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $results = array();
        $ga_service = new Fiftyonedegrees_Google_Analytics();
        $ga_service->get_custom_dimensions(); 
        $currCustDimIndex = get_option("fiftyonedegrees_ga_max_cust_dim_index", 0);
        $passedDims = get_option("fiftyonedegrees_passed_dimensions");

        foreach ($result["properties"] as $dataKey => $properties) {
            foreach ($properties as $property) {                
                if ( strpos(strtolower($property["name"]), "javascript") === false ) {
                    
                    // Get the property name.
                    $propertyName = strtolower($property["name"]);
                    
                    // Get the Custom Dimension Name Listbox for the property.
                    $custom_dimension_name = $this->get_custom_dimension_name($dataKey, $property["name"]);
                    $custom_dimension_list = $this->get_custom_dimension_listbox($custom_dimension_name);

                    // Get Custom Dimension Index for the property.
                    $custom_dimension_index = -2;
                    $actual_custom_dimension_name = $custom_dimension_name;

                    if( isset($passedDims[$propertyName]) ) {
                        $actual_custom_dimension_name = $passedDims[$propertyName];
                    }  
               
                    $ga_custom_dimension_index = $this->get_ga_custom_dimension_index( $actual_custom_dimension_name );
                    if( $ga_custom_dimension_index > -1 ) {
                        $custom_dimension_index = $ga_custom_dimension_index;
                    } 
                    else {
                        $currCustDimIndex = $currCustDimIndex + 1;
                        $custom_dimension_index = $currCustDimIndex;
                    } 

                    // Prepare table results
                    $results[] = array(
                      "property_name" => $propertyName,
                      "custom_dimension_name" => $custom_dimension_list,
                      "custom_dimension_index" => $custom_dimension_index
                    ); 
                    
                    $ga_results[] = array(
                        "property_name" => strtolower($property["name"]),
                        "custom_dimension_index" => $custom_dimension_index,
                        "custom_dimension_name" => $actual_custom_dimension_name,
                        "custom_dimension_ga_index" => $ga_custom_dimension_index,
                        "custom_dimension_datakey" => $dataKey
                    );                    
                }
               
            }
        }

        usort($results, function ($a, $b) {
            $orderby = (! empty($_GET['orderby'])) ? $_GET['orderby'] : 'custom_dimension_index';
            $order = (! empty($_GET['order'])) ? $_GET['order'] : 'asc';
            $result = 0;
            if ($a[$orderby] > $b[$orderby]) {
                $result = 1;
            }
            else if ($a[$orderby] < $b[$orderby]) {
                $result = -1;
            }
            return ($order === 'asc') ? $result : -$result;
        });

        update_option("fiftyonedegrees_ga_cust_dims_map", $ga_results);

        $this->items = $results;
    }

    public function display_rows()
    {
        $records = $this->items;

        foreach ($records as $i => $rec) {
            echo '<tr id="record_' . $i . '">';

            echo "<td>" . strtolower($rec["property_name"]) . "</td>";
           
            $passedDims = get_option("fiftyonedegrees_passed_dimensions");
            $listBoxId =  "51D_" . strtolower($rec["property_name"]);

            $selectedPHP = "";
            if ( isset($passedDims[$rec["property_name"]]) ) {
                $selectedPHP = $passedDims[$rec["property_name"]];
            }

            echo "<td>";
            echo "<div class='51DPropertiesList'>";
            ?>

            <select id="<?php echo $listBoxId; ?>" name = "<?php echo $listBoxId; ?>">
                <script>
                    var custDimsList = <?php echo json_encode($rec["custom_dimension_name"]);?>;
                    var selectedProperty = "<?php echo $selectedPHP; ?>";    
                        for(i=0; i<custDimsList.length; i++) {
                            if( selectedProperty == custDimsList[i]) {
                                document.write('<option value="' + custDimsList[i] +'" selected>' + custDimsList[i] + '</option>');
                            } else { 
                                document.write('<option value="' + custDimsList[i] +'">' + custDimsList[i] + '</option>');									
                            }                    
                    }
                </script>
            </select>
        <?php
            echo "</div>\n";
            echo "</td>\n";
            echo "<td>" . $rec["custom_dimension_index"] . "</td>\n";           
            echo "</tr>\n";
            
        }
    }

    /**
     * Retrieves Custom Dimension Name from Property. 
	 * @param string datakey
     * @param string property name
     * @return string Custom Dimension Name
     */
    public function get_custom_dimension_name( $datakey, $property ) {

		$cust_dim_name = "51D." . strtolower($datakey) . "." . strtolower($property);
        
        return $cust_dim_name;
    }

    /**
     * Retrieves Custom Dimension Index from Google Analytics.
     * @param string custom dimension name
     * @return int Google Analytic Index if dimension 
     * already exists otherwise returns -1.
     */
    public function get_ga_custom_dimension_index( $cust_dim_name ) {

        $ga_service = new Fiftyonedegrees_Google_Analytics();
        $result = $ga_service->get_custom_dimensions();
        $cust_dims_map = $result["cust_dims_map"];

        $cust_dim_index = -1;
        if( isset($cust_dims_map[$cust_dim_name]) ) {
            $cust_dim_index = $cust_dims_map[$cust_dim_name];
        }

        return $cust_dim_index;
    }

    /**
     * Retrieves Custom Dimension list box content. 
	 * @param string custom dimension name
     * @return array array list containing default and
     * existing custom dimensions.
     */	
    public function get_custom_dimension_listbox( $cust_dim_name ) {

        $ga_service = new Fiftyonedegrees_Google_Analytics();
        $result = $ga_service->get_custom_dimensions();

        $cust_dims_list = array();
         
        $list = array_keys($result["cust_dims_map"]);

        array_push($cust_dims_list, $cust_dim_name);

        foreach ($list as $item) {
            if($item !== $cust_dim_name) {
                array_push($cust_dims_list, $item);
            }
        }

        return $cust_dims_list;
    }

}
