<h2>51Degrees Device Detection</h2>

<?php

    if(isset($_GET["tab"])){

        $active_tab = $_GET["tab"];
    
    } else {

        $active_tab = "setup";

    }

?>

<h2 class="nav-tab-wrapper">
    <a href="?page=51Degrees&tab=setup" class="nav-tab <?php echo $active_tab == 'setup' ? 'nav-tab-active' : ''; ?>">Setup</a>
    <a href="?page=51Degrees&tab=properties" class="nav-tab <?php echo $active_tab == 'properties' ? 'nav-tab-active' : ''; ?>">Properties</a>
    <a href="?page=51Degrees&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
</h2>

<?php

include plugin_dir_path(__FILE__) . $active_tab . ".php";

?>
