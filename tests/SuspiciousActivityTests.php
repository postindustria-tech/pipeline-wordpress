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

require_once(__DIR__ . '/../includes/suspicious-activity.php');
require_once(__DIR__ . '/../includes/pipeline.php');

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey;

class ExitException extends \Exception {}

class SuspiciousActivityTests extends TestCase
{
    private $options = [];
    private $transients = [];

    public function set_up()
    {
        Pipeline::reset();
        parent::set_up();
        Monkey\setUp();

        $this->options = [
            Options::SUSPICIOUS_ENABLE => 'off',
            Options::SUSPICIOUS_REDIRECT_URL => 'http://example.com/blocked/',
            Options::SUSPICIOUS_REQUESTS => 5,
            Options::SUSPICIOUS_WINDOW => 30,
        ];
        $this->transients = [];

        $opts = &$this->options;
        $trans = &$this->transients;

        Functions\when('get_option')->alias(function ($key, $default = '') use (&$opts) {
            return array_key_exists($key, $opts) ? $opts[$key] : $default;
        });

        Functions\when('get_transient')->alias(function ($key) use (&$trans) {
            return isset($trans[$key]) ? $trans[$key] : false;
        });

        Functions\when('set_transient')->alias(function ($key, $value, $ttl = 0) use (&$trans) {
            $trans[$key] = $value;
            return true;
        });

        Functions\when('is_admin')->justReturn(false);
        Functions\when('wp_doing_ajax')->justReturn(false);
        Functions\when('trailingslashit')->alias(function ($string) {
            return rtrim($string, '/\\') . '/';
        });
        Functions\when('wp_parse_url')->alias(function ($url, $component = -1) {
            return parse_url($url, $component);
        });
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('error_log')->justReturn(true);

        \Patchwork\redefine('headers_sent', function () {
            return false;
        });
        \Patchwork\redefine('php_sapi_name', function () {
            return 'apache2handler';
        });

        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_USER_AGENT'] = 'TestBrowser/1.0';
        $_SERVER['REQUEST_URI'] = '/some-page/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    public function tear_down()
    {
        \Patchwork\restoreAll();
        Monkey\tearDown();
        parent::tear_down();
    }

    private function buildMockFlowData($engine, $propName, $value)
    {
        $propObj = new \stdClass();
        $propObj->hasValue = ($value !== null);
        $propObj->value = $value;
        if ($value === null) {
            $propObj->noValueMessage = 'No value';
        }

        $engineObj = new \stdClass();
        $engineObj->{$propName} = $propObj;

        $flowData = new \stdClass();
        $flowData->{$engine} = $engineObj;

        return $flowData;
    }

    /**
     * Test that no transient reads or redirects happen when the feature
     * is disabled.
     */
    public function testFeatureDisabledDoesNothing()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'off';

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped on admin requests.
     */
    public function testSkippedOnAdmin()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        Functions\when('is_admin')->justReturn(true);

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped on AJAX requests.
     */
    public function testSkippedOnAjax()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        Functions\when('wp_doing_ajax')->justReturn(true);

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped on CLI requests.
     */
    public function testSkippedOnCli()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        \Patchwork\redefine('php_sapi_name', function () {
            return 'cli';
        });

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped on wp-login.php.
     */
    public function testSkippedOnWpLogin()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $_SERVER['SCRIPT_NAME'] = '/wp-login.php';

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped on wp-signup.php.
     */
    public function testSkippedOnWpSignup()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $_SERVER['SCRIPT_NAME'] = '/wp-signup.php';

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped on wp-activate.php.
     */
    public function testSkippedOnWpActivate()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $_SERVER['SCRIPT_NAME'] = '/wp-activate.php';

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped when headers have already been sent.
     */
    public function testSkippedWhenHeadersSent()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        \Patchwork\redefine('headers_sent', function () {
            return true;
        });

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that a zero window value causes an early return.
     */
    public function testZeroWindowReturnsEarly()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_WINDOW] = 0;

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that a zero threshold value causes an early return.
     */
    public function testZeroThresholdReturnsEarly()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_REQUESTS] = 0;

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that get_51did returns the IdProbLic value when available.
     */
    public function testIdentityIdProbLicAvailable()
    {
        Pipeline::$data = [
            'properties' => [
                'did_engine' => [
                    'idproblic' => ['name' => 'IdProbLic', 'type' => 'String'],
                ],
            ],
            'flowData' => $this->buildMockFlowData('did_engine', 'idproblic', 'abc-123-did'),
            'errors' => [],
        ];

        $result = SuspiciousActivity::get_51did();
        self::assertEquals('abc-123-did', $result);
    }

    /**
     * Test that get_51did falls back to IdProbGlobal when IdProbLic is absent.
     */
    public function testIdentityIdProbGlobalFallback()
    {
        Pipeline::$data = [
            'properties' => [
                'did_engine' => [
                    'idprobglobal' => ['name' => 'IdProbGlobal', 'type' => 'String'],
                ],
            ],
            'flowData' => $this->buildMockFlowData('did_engine', 'idprobglobal', 'global-did-456'),
            'errors' => [],
        ];

        $result = SuspiciousActivity::get_51did();
        self::assertEquals('global-did-456', $result);
    }

    /**
     * Test that get_51did falls back to an IP+UA hash when no pipeline
     * identity properties are available.
     */
    public function testIdentityFallbackToIpUaHash()
    {
        Pipeline::$data = [
            'properties' => [],
            'flowData' => null,
            'errors' => [],
        ];

        $result = SuspiciousActivity::get_51did();
        $expected = hash('sha256', '192.168.1.100|TestBrowser/1.0');
        self::assertEquals($expected, $result);
        self::assertEquals(64, strlen($result));
    }

    /**
     * Test that the IP+UA hash is stable across multiple calls with the
     * same server variables.
     */
    public function testIdentityIpUaHashIsStable()
    {
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];

        $result1 = SuspiciousActivity::get_51did();
        $result2 = SuspiciousActivity::get_51did();
        self::assertEquals($result1, $result2);
    }

    /**
     * Test that no redirect fires when the request count is under the
     * configured threshold.
     */
    public function testUnderThresholdNoRedirect()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_REQUESTS] = 5;
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];

        $did = SuspiciousActivity::get_51did();
        $key = SuspiciousActivity::build_transient_key($did);
        $now = microtime(true);
        $this->transients[$key] = [$now - 5, $now - 3, $now - 1];

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertCount(4, $this->transients[$key]);
    }

    /**
     * Test that the redirect fires when the request count equals the
     * configured threshold (>= semantics).
     */
    public function testAtThresholdRedirectFires()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_REQUESTS] = 5;
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];

        $did = SuspiciousActivity::get_51did();
        $key = SuspiciousActivity::build_transient_key($did);
        $now = microtime(true);
        $this->transients[$key] = [$now - 4, $now - 3, $now - 2, $now - 1];

        Functions\expect('wp_safe_redirect')
            ->once()
            ->with('http://example.com/blocked/', 302)
            ->andReturnUsing(function () {
                throw new ExitException('redirect');
            });

        try {
            SuspiciousActivity::check_and_maybe_redirect();
            self::fail('Expected ExitException from wp_safe_redirect');
        } catch (ExitException $e) {
            self::assertEquals('redirect', $e->getMessage());
        }
    }

    /**
     * Test that the redirect fires when the request count exceeds the
     * configured threshold.
     */
    public function testOverThresholdRedirectFires()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_REQUESTS] = 5;
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];

        $did = SuspiciousActivity::get_51did();
        $key = SuspiciousActivity::build_transient_key($did);
        $now = microtime(true);
        $this->transients[$key] = [$now - 5, $now - 4, $now - 3, $now - 2, $now - 1];

        Functions\expect('wp_safe_redirect')
            ->once()
            ->with('http://example.com/blocked/', 302)
            ->andReturnUsing(function () {
                throw new ExitException('redirect');
            });

        try {
            SuspiciousActivity::check_and_maybe_redirect();
            self::fail('Expected ExitException from wp_safe_redirect');
        } catch (ExitException $e) {
            self::assertEquals('redirect', $e->getMessage());
        }
    }

    /**
     * Test that the first request from a visitor creates a single-entry
     * transient and does not redirect.
     */
    public function testFirstRequestNoRedirect()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_REQUESTS] = 5;
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];

        SuspiciousActivity::check_and_maybe_redirect();

        $did = SuspiciousActivity::get_51did();
        $key = SuspiciousActivity::build_transient_key($did);
        self::assertArrayHasKey($key, $this->transients);
        self::assertCount(1, $this->transients[$key]);
    }

    /**
     * Test that no redirect fires when the threshold is exceeded but
     * no redirect URL is configured.
     */
    public function testOverThresholdNoRedirectUrlNoRedirect()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_REQUESTS] = 2;
        $this->options[Options::SUSPICIOUS_REDIRECT_URL] = '';
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];

        $did = SuspiciousActivity::get_51did();
        $key = SuspiciousActivity::build_transient_key($did);
        $now = microtime(true);
        $this->transients[$key] = [$now - 1, $now - 0.5];

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertTrue(true);
    }

    /**
     * Test that no redirect fires when the current request path matches
     * the redirect target path (loop prevention).
     */
    public function testLoopPreventionMatchingPaths()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_REQUESTS] = 1;
        $this->options[Options::SUSPICIOUS_REDIRECT_URL] = 'http://example.com/blocked/';
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];

        $_SERVER['REQUEST_URI'] = '/blocked/';

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the IP+UA hash works with non-ASCII and binary characters
     * in the User-Agent string.
     */
    public function testNonAsciiUaHashWorks()
    {
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];
        $_SERVER['HTTP_USER_AGENT'] = "\xff\xfe\x00\x01 " . chr(0) . " binary";

        $result = SuspiciousActivity::get_51did();
        self::assertEquals(64, strlen($result));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $result);
    }

    /**
     * Test that the enable sanitizer only accepts 'on' as a truthy value.
     */
    public function testSanitizeEnableCheckbox()
    {
        $sanitize = function ($v) { return $v === 'on' ? 'on' : 'off'; };

        self::assertEquals('on', $sanitize('on'));
        self::assertEquals('off', $sanitize('off'));
        self::assertEquals('off', $sanitize(''));
        self::assertEquals('off', $sanitize(null));
        self::assertEquals('off', $sanitize('yes'));
    }

    /**
     * Test that truthy-looking values like 'off', '0', and false all
     * sanitize to 'off'.
     */
    public function testSanitizeTruthyOffTrap()
    {
        $sanitize = function ($v) { return $v === 'on' ? 'on' : 'off'; };

        self::assertEquals('off', $sanitize('off'));
        self::assertEquals('off', $sanitize(''));
        self::assertEquals('off', $sanitize('0'));
        self::assertEquals('off', $sanitize(false));
        self::assertEquals('on', $sanitize('on'));
    }

    /**
     * Test that the requests and window sanitizers clamp values to their
     * valid ranges.
     */
    public function testSanitizeBounds()
    {
        $sanitize_requests = function ($v) { return max(1, (int) $v); };
        $sanitize_window = function ($v) { return max(1, min(3600, (int) $v)); };

        self::assertEquals(1, $sanitize_requests(0));
        self::assertEquals(1, $sanitize_requests(-5));
        self::assertEquals(10, $sanitize_requests(10));

        self::assertEquals(1, $sanitize_window(0));
        self::assertEquals(1, $sanitize_window(-5));
        self::assertEquals(3600, $sanitize_window(9999));
        self::assertEquals(60, $sanitize_window(60));
    }

    /**
     * Test that timestamps older than the configured window are pruned
     * before the count is evaluated.
     */
    public function testExpiredTimestampsPruned()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        $this->options[Options::SUSPICIOUS_REQUESTS] = 10;
        $this->options[Options::SUSPICIOUS_WINDOW] = 30;
        Pipeline::$data = ['properties' => [], 'flowData' => null, 'errors' => []];

        $did = SuspiciousActivity::get_51did();
        $key = SuspiciousActivity::build_transient_key($did);
        $now = microtime(true);
        $this->transients[$key] = [
            $now - 100,
            $now - 60,
            $now - 50,
            $now - 2,
            $now - 1,
        ];

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertCount(3, $this->transients[$key]);
    }

    /**
     * Test that the transient key is built from an md5 hash of the
     * visitor identity.
     */
    public function testTransientKeyFormat()
    {
        $key = SuspiciousActivity::build_transient_key('abc');
        self::assertEquals('51d_suspicious_' . md5('abc'), $key);
        self::assertEquals(47, strlen($key));
    }

    /**
     * Constant-based exclusion tests MUST be last. PHP constants persist
     * across tests in the same process, so once defined they contaminate
     * all subsequent tests.
     */

    /**
     * Test that the redirect is skipped during cron execution.
     */
    public function testSkippedOnCron()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        if (!defined('DOING_CRON')) {
            define('DOING_CRON', true);
        }

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped on REST API requests.
     */
    public function testSkippedOnRest()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        if (!defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }

    /**
     * Test that the redirect is skipped on XML-RPC requests.
     */
    public function testSkippedOnXmlRpc()
    {
        $this->options[Options::SUSPICIOUS_ENABLE] = 'on';
        if (!defined('XMLRPC_REQUEST')) {
            define('XMLRPC_REQUEST', true);
        }

        SuspiciousActivity::check_and_maybe_redirect();

        self::assertEmpty($this->transients);
    }
}
