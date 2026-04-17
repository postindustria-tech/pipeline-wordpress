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

require_once __DIR__ . '/../options.php';

/**
 * Suspicious activity detection engine.
 *
 * Tracks per-visitor request rates using WordPress transients and
 * redirects visitors who exceed a configured threshold.
 *

 */
class SuspiciousActivity
{
    /**
     * Registers the init hook for suspicious activity checking.
     *
     * @access public
    
     * @return void
     */
    public static function register()
    {
        add_action('init', [__CLASS__, 'check_and_maybe_redirect'], 11);
    }

    /**
     * Main entry point called on every init at priority 11.
     * Checks exclusions, records the request, and redirects if threshold met.
     *
     * @access public
    
     * @return void
     */
    public static function check_and_maybe_redirect()
    {
        if (get_option(Options::SUSPICIOUS_ENABLE, 'off') !== 'on') {
            return;
        }

        if (self::is_excluded_context()) {
            return;
        }

        $window = (int) get_option(Options::SUSPICIOUS_WINDOW, 30);
        $threshold = (int) get_option(Options::SUSPICIOUS_REQUESTS, 5);

        if ($window <= 0 || $threshold <= 0) {
            return;
        }

        if (self::is_on_redirect_target()) {
            return;
        }

        if (headers_sent()) {
            error_log('Suspicious activity: cannot redirect, headers already sent');
            return;
        }

        $did = self::get_51did();
        $key = self::build_transient_key($did);
        $now = microtime(true);

        $timestamps = get_transient($key);
        if (!is_array($timestamps)) {
            $timestamps = [];
        }

        // Prune expired entries.
        $timestamps = array_values(array_filter(
            $timestamps,
            function ($t) use ($now, $window) {
                return $t >= $now - $window;
            }
        ));

        $timestamps[] = $now;
        set_transient($key, $timestamps, $window);

        if (count($timestamps) >= $threshold) {
            $target = get_option(Options::SUSPICIOUS_REDIRECT_URL);
            if (!$target) {
                return;
            }
            wp_safe_redirect($target, 302);
            exit;
        }
    }

    /**
     * Resolves the visitor identity.
     * Tries IdProbLic then IdProbGlobal from the pipeline, falling back
     * to a SHA-256 hash of IP + User-Agent.
     *
     * @access public
    
     * @return string visitor identifier (always returns a value)
     */
    public static function get_51did()
    {
        $properties = Pipeline::$data['properties'] ?? [];

        foreach (['idproblic', 'idprobglobal'] as $propName) {
            foreach ($properties as $engine => $props) {
                if (isset($props[$propName])) {
                    $value = Pipeline::get($engine, $propName);
                    if ($value !== null && $value !== '') {
                        return (string) $value;
                    }
                }
            }
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $ua = sanitize_text_field(
            wp_unslash(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')
        );
        return hash('sha256', $ip . '|' . $ua);
    }

    /**
     * Finds the engine dataKey that exposes IdProbLic or IdProbGlobal.
     * Used by the admin UI to display the active tracking mode.
     *
     * @access public
    
     * @return string|null engine dataKey, or null if not available
     */
    public static function id_engine_datakey()
    {
        $properties = Pipeline::$data['properties'] ?? [];
        foreach ($properties as $engine => $props) {
            if (isset($props['idproblic']) || isset($props['idprobglobal'])) {
                return $engine;
            }
        }
        return null;
    }

    /**
     * Records a request timestamp and returns the new count within the window.
     *
     * @access public
    
     * @param  string $did   visitor identifier
     * @return int    number of requests in the current window
     */
    public static function record_request($did)
    {
        $key = self::build_transient_key($did);
        $window = (int) get_option(Options::SUSPICIOUS_WINDOW, 30);
        $now = microtime(true);

        $timestamps = get_transient($key);
        if (!is_array($timestamps)) {
            $timestamps = [];
        }

        $timestamps = array_values(array_filter(
            $timestamps,
            function ($t) use ($now, $window) {
                return $t >= $now - $window;
            }
        ));

        $timestamps[] = $now;
        set_transient($key, $timestamps, $window);

        return count($timestamps);
    }

    /**
     * Builds the transient key for a given visitor identity.
     *
     * @access public
    
     * @param  string $did visitor identifier
     * @return string transient key (47 chars: "51d_suspicious_" + md5)
     */
    public static function build_transient_key($did)
    {
        return '51d_suspicious_' . md5($did);
    }

    /**
     * Returns true if the current request context should be excluded
     * from suspicious activity tracking.
     *
     * @access private
    
     * @return bool
     */
    private static function is_excluded_context()
    {
        if (is_admin()) {
            return true;
        }

        if (wp_doing_ajax()) {
            return true;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return true;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return true;
        }

        if (php_sapi_name() === 'cli') {
            return true;
        }

        $script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
        if (in_array($script, ['wp-login.php', 'wp-signup.php', 'wp-activate.php'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the current request is for the configured redirect
     * target page (loop prevention).
     *
     * @access private
    
     * @return bool
     */
    private static function is_on_redirect_target()
    {
        $target = get_option(Options::SUSPICIOUS_REDIRECT_URL);
        if (!$target) {
            return false;
        }

        $current_path = trailingslashit(
            strtok(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', '?')
        );
        $target_path = trailingslashit(
            wp_parse_url($target, PHP_URL_PATH) ?: '/'
        );

        return $current_path === $target_path;
    }
}

if (function_exists('add_action')) {
    SuspiciousActivity::register();
}
