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

<p>To get started visit <a href="https://configure.51degrees.com/" target="_blank">https://configure.51degrees.com/</a> to get a 51Degrees resource key for the device detection properties you want to get access to.</p>

<form method="post" action="options.php">

    <?php settings_fields('fiftyonedegrees_options'); ?>

    <?php

        $cachedPipeline = get_option('fiftyonedegrees_resource_key_pipeline');

        if(!$cachedPipeline){

            echo '<span class="fod-pipeline-status error">You need to enter a resource key</span>';

        }

        if(isset($cachedPipeline['error'])){

            echo '<span class="fod-pipeline-status error">'. $cachedPipeline['error'] .'</span>';

        }

        if(isset($cachedPipeline['pipeline'])){

            echo '<span class="fod-pipeline-status good">Resource Key is valid and gives you access to the following engines: ' . json_encode($cachedPipeline['available_engines']) .' </span>';

        }

    ?>

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="fiftyonedegrees_resource_key">Resource Key</label></th>
                <td>
                    <input name="fiftyonedegrees_resource_key" type="text" id="fiftyonedegrees_resource_key" value="<?php echo get_option("fiftyonedegrees_resource_key");?>" class="regular-text">
                </td>
            </tr>
        </tbody>
    </table>

    <?php submit_button();?>

</form>