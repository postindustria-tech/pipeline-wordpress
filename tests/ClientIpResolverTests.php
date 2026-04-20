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

require_once(__DIR__ . "/../includes/client-ip.php");
require_once(__DIR__ . "/../options.php");

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use \Brain\Monkey\Functions;
use \Brain\Monkey;


class ClientIpResolverTests extends TestCase {

    public function set_up() {
        parent::set_up();
        Brain\Monkey\setUp();
    }

    public function tear_down() {
        Brain\Monkey\tearDown();
        parent::tear_down();
    }

    /**
     * Stub get_option so that Options::TRUSTED_PROXY_HEADER returns
     * the given value, all other options fall through to their default.
     */
    private function mockOption(string $value): void {
        Functions\when('get_option')->alias(function ($name, $default = null) use ($value) {
            if ($name === Options::TRUSTED_PROXY_HEADER) {
                return $value;
            }
            return $default;
        });
    }

    /**
     * Test that 'Disabled' source ignores all proxy headers and
     * returns REMOTE_ADDR.
     */
    public function testDisabledReturnsRemoteAddrEvenWhenHeadersPresent() {
        $this->mockOption('disabled');
        $this->assertSame('203.0.113.1', ClientIpResolver::resolve([
            'REMOTE_ADDR' => '203.0.113.1',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.5',
            'HTTP_X_FORWARDED_FOR'  => '198.51.100.6',
        ]));
    }

    /**
     * Test that 'Cloudflare' reads CF-Connecting-IP.
     */
    public function testCloudflareReadsCfConnectingIp() {
        $this->mockOption('cloudflare');
        $this->assertSame('198.51.100.5', ClientIpResolver::resolve([
            'HTTP_CF_CONNECTING_IP' => '198.51.100.5',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that 'Akamai / CF Enterprise' reads True-Client-IP.
     */
    public function testTrueClientReadsTrueClientIp() {
        $this->mockOption('true-client');
        $this->assertSame('198.51.100.5', ClientIpResolver::resolve([
            'HTTP_TRUE_CLIENT_IP' => '198.51.100.5',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that 'Nginx' reads X-Real-IP.
     */
    public function testXRealIpReadsXRealIp() {
        $this->mockOption('x-real-ip');
        $this->assertSame('198.51.100.5', ClientIpResolver::resolve([
            'HTTP_X_REAL_IP' => '198.51.100.5',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that 'Generic' reads X-Forwarded-For.
     */
    public function testXForwardedReadsXForwardedFor() {
        $this->mockOption('x-forwarded');
        $this->assertSame('198.51.100.5', ClientIpResolver::resolve([
            'HTTP_X_FORWARDED_FOR' => '198.51.100.5',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that 'Legacy' reads Client-IP.
     */
    public function testClientIpReadsClientIp() {
        $this->mockOption('client-ip');
        $this->assertSame('198.51.100.5', ClientIpResolver::resolve([
            'HTTP_CLIENT_IP' => '198.51.100.5',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that a selected source ignores other proxy headers an
     * attacker may have injected.
     */
    public function testCloudflareSelectedIgnoresXForwardedFor() {
        $this->mockOption('cloudflare');
        $this->assertSame('198.51.100.5', ClientIpResolver::resolve([
            'HTTP_CF_CONNECTING_IP' => '198.51.100.5',
            'HTTP_X_FORWARDED_FOR'  => 'attacker.spoof',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that X-Forwarded-For takes the first entry from the
     * comma-separated list.
     */
    public function testXForwardedTakesFirstEntry() {
        $this->mockOption('x-forwarded');
        $this->assertSame('203.0.113.1', ClientIpResolver::resolve([
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1, 10.0.0.1, 10.0.0.2',
            'REMOTE_ADDR' => '127.0.0.1',
        ]));
    }

    /**
     * Test that whitespace around X-Forwarded-For entries is trimmed.
     */
    public function testXForwardedTrimmedWhitespace() {
        $this->mockOption('x-forwarded');
        $this->assertSame('203.0.113.1', ClientIpResolver::resolve([
            'HTTP_X_FORWARDED_FOR' => '  203.0.113.1  , 10.0.0.1',
            'REMOTE_ADDR' => '127.0.0.1',
        ]));
    }

    /**
     * Test that if the selected header is not present on the request,
     * the resolver falls back to REMOTE_ADDR.
     */
    public function testSelectedHeaderMissingFallsBackToRemoteAddr() {
        $this->mockOption('cloudflare');
        $this->assertSame('203.0.113.1', ClientIpResolver::resolve([
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that a syntactically malformed value in the selected header
     * triggers fallback to REMOTE_ADDR.
     */
    public function testSelectedHeaderMalformedFallsBackToRemoteAddr() {
        $this->mockOption('cloudflare');
        $this->assertSame('203.0.113.1', ClientIpResolver::resolve([
            'HTTP_CF_CONNECTING_IP' => 'not-an-ip',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that when XFF is selected and its first entry is malformed,
     * the resolver falls back to REMOTE_ADDR rather than reading later
     * entries (which are proxies, not the client).
     */
    public function testXForwardedFirstEntryMalformedFallsBackToRemoteAddr() {
        $this->mockOption('x-forwarded');
        $this->assertSame('203.0.113.1', ClientIpResolver::resolve([
            'HTTP_X_FORWARDED_FOR' => 'garbage, 203.0.113.5',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that valid IPv6 values in the selected header are accepted
     * as-is.
     */
    public function testIpv6Accepted() {
        $this->mockOption('cloudflare');
        $this->assertSame('2001:4860:4860::8888', ClientIpResolver::resolve([
            'HTTP_CF_CONNECTING_IP' => '2001:4860:4860::8888',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that private-range IPs (e.g. 10.0.0.1) in the selected
     * header are accepted without filtering.
     */
    public function testPrivateIpAcceptedWhenChosenSourceProvidesIt() {
        $this->mockOption('x-real-ip');
        $this->assertSame('10.0.0.1', ClientIpResolver::resolve([
            'HTTP_X_REAL_IP' => '10.0.0.1',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that when neither a selected header nor REMOTE_ADDR is
     * available, the resolver returns an empty string.
     */
    public function testEmptyServerReturnsEmptyString() {
        $this->mockOption('disabled');
        $this->assertSame('', ClientIpResolver::resolve([]));
    }

    /**
     * Test that REMOTE_ADDR is returned without FILTER_VALIDATE_IP
     * (it is server-controlled, not client-forgeable).
     */
    public function testRemoteAddrPassesThroughWithoutValidation() {
        $this->mockOption('disabled');
        $this->assertSame('anything-goes', ClientIpResolver::resolve([
            'REMOTE_ADDR' => 'anything-goes',
        ]));
    }

    /**
     * Test that a non-string value from get_option (e.g. null when the
     * option row is missing in an unmocked test env) is handled
     * defensively without triggering a deprecation warning.
     */
    public function testNonStringOptionValueTreatedAsDisabled() {
        Functions\when('get_option')->alias(function ($name, $default = null) {
            return null;
        });
        $this->assertSame('203.0.113.1', ClientIpResolver::resolve([
            'HTTP_CF_CONNECTING_IP' => '198.51.100.5',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    /**
     * Test that a dropdown value not on the sanitizer allow-list is
     * treated as 'disabled' on read (defence in depth against stale
     * DB values or direct-DB tampering).
     */
    public function testUnknownOptionValueTreatedAsDisabled() {
        $this->mockOption('something-the-sanitizer-should-have-rejected');
        $this->assertSame('203.0.113.1', ClientIpResolver::resolve([
            'HTTP_CF_CONNECTING_IP' => '198.51.100.5',
            'REMOTE_ADDR' => '203.0.113.1',
        ]));
    }

    // --- sanitizeSource (registered as the register_setting sanitize_callback) ---

    /**
     * Test that every allow-listed dropdown value survives the
     * sanitizer unchanged.
     */
    public function testSanitizeSourceAcceptsAllAllowedValues() {
        foreach (['disabled', 'cloudflare', 'true-client', 'x-real-ip', 'x-forwarded', 'client-ip'] as $value) {
            $this->assertSame($value, ClientIpResolver::sanitizeSource($value));
        }
    }

    /**
     * Test that values not on the allow-list are coerced to 'disabled',
     * preventing arbitrary $_SERVER keys from reaching the resolver.
     */
    public function testSanitizeSourceRejectsUnknownValues() {
        $this->assertSame('disabled', ClientIpResolver::sanitizeSource('CLOUDFLARE'));
        $this->assertSame('disabled', ClientIpResolver::sanitizeSource('HTTP_X_FORWARDED_FOR'));
        $this->assertSame('disabled', ClientIpResolver::sanitizeSource(''));
        $this->assertSame('disabled', ClientIpResolver::sanitizeSource(null));
        $this->assertSame('disabled', ClientIpResolver::sanitizeSource([]));
        $this->assertSame('disabled', ClientIpResolver::sanitizeSource(42));
    }
}
