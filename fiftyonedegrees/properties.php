<p>Below is a list of properties available with your resourcekey.</p>

<?php

// Properties table

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/screen.php' );
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Properties_List_table extends WP_List_Table {

     function get_columns() {
        return $columns= array(
           'col_property_name'=>__('Name'),
           'col_property_type'=>__('Type'),
           'col_property_category'=>__('Category'),
        );
     }

     public function get_sortable_columns() {
        return $sortable = array(
           'col_property_name'=> array('col_property_name',false),
           'col_property_type'=> array('col_property_type',false),
           'col_property_category'=> array('col_property_category',false)
        );
     }

     public function prepare_items(){

        $result = fiftyonedegrees::process();

        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $results = array();

        foreach ($result["properties"] as $property){

            $results[] = array(
                "col_property_name" => $property["Name"],
                "col_property_category" => $property["Type"],
                "col_property_type" => $property["Category"]
            );

        }

          usort($results, function( $a, $b ) {
            $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'col_property_name';
            $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
            $result = strcmp( $a[$orderby], $b[$orderby] );
            return ( $order === 'asc' ) ? $result : -$result;
          });

          $this->items = $results;


     }

     function display_rows() {

        $records = $this->items;
     
        $columns = $this->get_column_info();
     
        foreach($records as $i => $rec){
     
             echo '<tr id="record_' . $i . '">';
    
             echo "<td>" . strtolower($rec["col_property_name"]) . "</td>";
             echo "<td>" . $rec["col_property_category"] . "</td>";
             echo "<td>" . $rec["col_property_type"] . "</td>";
            
             echo'</tr>';
             
        }
     

    }  
}
 
 //Prepare Table of elements
$propertyTable = new Properties_List_table();

$propertyTable->prepare_items();

$propertyTable->display();

?>
