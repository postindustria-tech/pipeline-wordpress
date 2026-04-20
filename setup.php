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

<form method="post" action="options.php">

    <?php settings_fields(Options::GROUP_KEY); ?>

    <?php

        $cachedPipeline = get_option(Options::PIPELINE);

        if (isset($cachedPipeline['error'])) {
            echo '<p></p><span class="fod-pipeline-status error"><b>' .
                esc_html($cachedPipeline['error']) . '</b></span>';
        }

        if (isset($cachedPipeline['pipeline'])) {
            echo '<p></p><span class="fod-pipeline-status good"><b>This ' .
                'Resource Key is valid and allows access to the custom ' .
                'properties selected in the following categories: ' .
                esc_html(json_encode($cachedPipeline['available_engines'])) .
                ' </br>To continue, connect to Google Analytics via the ' .
                '<a href="options-general.php?page=51Degrees&tab=google-analytics">' .
                'Google Analytics</a> tab. See the ' .
                '<a href="options-general.php?page=51Degrees&tab=properties">Properties</a>' .
                ' tab for a list of all the custom properties.</b></span>';
        }

    ?>

    <p>
        To get started visit
        <a href="https://configure.51degrees.com/" target="_blank">
            https://configure.51degrees.com/
        </a>
        to get a 51Degrees Resource Key for the device detection properties you
        want to get access to.
        </br>
        For more information on how to use our Configurator, view our explainer
        video
        <a href="https://51degrees.com/documentation/_concepts__configurator.html" target="_blank">
            here
        </a>.
    </p>
    
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="<?php echo Options::RESOURCE_KEY; ?>">Resource Key</label>
                </th>
                <td>
                    <input name="<?php echo Options::RESOURCE_KEY; ?>" type="text" id="<?php echo Options::RESOURCE_KEY; ?>" value="<?php echo esc_attr(get_option(Options::RESOURCE_KEY));?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?php echo Options::TRUSTED_PROXY_HEADER; ?>">Trusted client-IP source</label>
                </th>
                <td>
                    <?php $current = get_option(Options::TRUSTED_PROXY_HEADER, 'disabled'); ?>
                    <select name="<?php echo Options::TRUSTED_PROXY_HEADER; ?>" id="<?php echo Options::TRUSTED_PROXY_HEADER; ?>">
                        <option value="disabled"    <?php selected($current, 'disabled');    ?>>Disabled</option>
                        <option value="cloudflare"  <?php selected($current, 'cloudflare');  ?>>Cloudflare (CF-Connecting-IP)</option>
                        <option value="true-client" <?php selected($current, 'true-client'); ?>>Akamai / CF Enterprise (True-Client-IP)</option>
                        <option value="x-real-ip"   <?php selected($current, 'x-real-ip');   ?>>Nginx (X-Real-IP)</option>
                        <option value="x-forwarded" <?php selected($current, 'x-forwarded'); ?>>Generic (X-Forwarded-For)</option>
                        <option value="client-ip"   <?php selected($current, 'client-ip');   ?>>Legacy (Client-IP)</option>
                    </select>
                    <p class="description">
                        Most sites don&#39;t need to change this. If your site sits behind Cloudflare, Nginx, Akamai,
                        or another service that passes traffic through to WordPress, pick the matching option so visitors
                        are identified by their real IP address rather than the intermediary&#39;s.
                        If you&#39;re not sure, leave it set to &quot;Disabled&quot;.
                    </p>
                </td>
            </tr>
        </tbody>
    </table>

    <input type="submit" class="button-primary" value="Save Changes"/>

</form>

