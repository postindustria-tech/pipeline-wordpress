<form method="post" action="options.php">
    
    <?php settings_fields('fiftyonedegrees_options'); ?>

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="fiftyonedegrees_resource_key">Resource Key</label></th>
                <td>
                    <input name="fiftyonedegrees_resource_key" type="text" id="fiftyonedegrees_resource_key" value="<?php echo get_option("fiftyonedegrees_resource_key");?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="fiftyonedegrees_license_key">License Key</label></th>
                <td>
                    <input name="fiftyonedegrees_license_key" type="text" id="fiftyonedegrees_license_key" value="<?php echo get_option("fiftyonedegrees_license_key");?>" class="regular-text">
                </td>
            </tr>
        </tbody>
    </table>

    <?php submit_button();?>

</form>