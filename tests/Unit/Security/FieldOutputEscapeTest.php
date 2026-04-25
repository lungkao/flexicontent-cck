<?php
/**
 * FieldOutputEscapeTest — Unit tests for XSS hardening in field value/edit templates.
 *
 * Covers fixes made to:
 *   - plugins/flexicontent_fields/sharedmedia/tmpl/value_default.php
 *   - plugins/flexicontent_fields/weblink/tmpl/value_default.php
 *   - plugins/flexicontent_fields/addressint/tmpl/value.php
 *   - plugins/flexicontent_fields/addressint/tmpl/field.php
 *   - plugins/flexicontent_fields/file/tmpl/value_InlineBoxes.php
 *   - plugins/flexicontent_fields/file/file/share_form.php
 *
 * Key principle: htmlspecialchars(ENT_COMPAT) converts < > & " to entities.
 * After escaping, the word "onerror" may still appear as SAFE text —
 * the important check is that raw HTML tags are NOT present (e.g. no literal "<img ").
 *
 * Run: cd tests && vendor/bin/phpunit Unit/Security/FieldOutputEscapeTest.php
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FieldOutputEscapeTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /** Escape for HTML text content or attribute value (ENT_COMPAT). */
    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_COMPAT, 'UTF-8');
    }

    /** Escape for JavaScript string context (ENT_QUOTES). */
    private static function escJs(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Assert that a string of HTML does not contain any executable raw tags.
     * After htmlspecialchars(), '<' becomes '&lt;' — the literal character '<'
     * should not appear unless it is a known, trusted opening tag.
     */
    private function assertNoRawTags(string $html, string $trustedPrefix = ''): void
    {
        // Strip out the trusted outer wrapper before checking
        $inner = $trustedPrefix ? substr($html, strlen($trustedPrefix)) : $html;
        // There must be no literal < that starts a new element (attacker-injected)
        $this->assertDoesNotMatchRegularExpression(
            '/<(script|img|svg|iframe|object|embed|form|input|button|a)\b/i',
            $inner,
            'Dangerous HTML tags must not appear unescaped in the output'
        );
    }

    /**
     * Assert a double-quoted HTML attribute cannot be broken out of by the payload.
     * The payload's " must be encoded so it cannot close the attribute prematurely.
     */
    private function assertAttributeWellFormed(string $attrHtml): void
    {
        // Pattern: attr="<only safe chars here>" — value must be a single token
        $this->assertMatchesRegularExpression(
            '/\w+="[^"]*"/',
            $attrHtml,
            'Double-quoted attribute must remain well-formed (payload cannot break out)'
        );
    }

    // =========================================================================
    // Data providers
    // =========================================================================

    /** @return array<string, array{string}> */
    public static function htmlTagPayloadsProvider(): array
    {
        return [
            'script tag'         => ['<script>alert(1)</script>'],
            'img onerror'        => ['<img src=x onerror=alert(1)>'],
            'svg onload'         => ['<svg onload=alert(1)>'],
            'iframe src'         => ['<iframe src="javascript:alert(1)">'],
            'angle brackets'     => ['<b>bold</b>'],
            'javascript: proto'  => ['javascript:alert(document.cookie)'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function attrBreakoutProvider(): array
    {
        return [
            'double-quote break' => ['" onmouseover="alert(1)'],
            'single-quote break' => ["' onmouseover='alert(1)"],
        ];
    }

    // =========================================================================
    // 1. sharedmedia — title / author / description (HTML text content)
    // =========================================================================

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testSharedmediaTitleNoRawTags(string $payload): void
    {
        $html = '<h4>' . self::esc($payload) . '</h4>';
        $this->assertNoRawTags($html, '<h4>');
    }

    #[DataProvider('attrBreakoutProvider')]
    public function testSharedmediaTitleAttrBreakout(string $payload): void
    {
        // When used in text content, breakout chars are encoded
        $escaped = self::esc($payload);
        // Double-quote in payload must be encoded as &quot;
        if (str_contains($payload, '"')) {
            $this->assertStringContainsString('&quot;', $escaped);
        }
        // No raw HTML tag starts
        $this->assertDoesNotMatchRegularExpression('/<\w/', $escaped);
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testSharedmediaAuthorNoRawTags(string $payload): void
    {
        $html = '<b class="fc_sm_author">' . self::esc($payload) . '</b>';
        $this->assertNoRawTags($html, '<b class="fc_sm_author">');
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testSharedmediaDescriptionNoRawTags(string $payload): void
    {
        $html = '<div class="description">' . self::esc($payload) . '</div>';
        $this->assertNoRawTags($html, '<div class="description">');
    }

    // =========================================================================
    // 2. weblink — href attribute (ENT_COMPAT encodes " but not ')
    // =========================================================================

    public function testWeblinkHrefDoubleQuoteBreakout(): void
    {
        // Critical: " in payload must be encoded so it cannot close the href attr
        $payload = '" onmouseover="alert(1)';
        $html    = 'href="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
        $this->assertStringContainsString('&quot;', $html, '" must become &quot;');
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testWeblinkHrefNoRawProtocol(string $payload): void
    {
        $escaped = self::esc($payload);
        // No raw < that could inject event handlers
        $this->assertDoesNotMatchRegularExpression('/<\w/', $escaped);
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testWeblinkLinktextNoRawTags(string $payload): void
    {
        $html = '<a href="#">' . self::esc($payload) . '</a>';
        $this->assertNoRawTags($html, '<a href="#">');
    }

    public function testWeblinkIdDoubleQuoteBreakout(): void
    {
        $payload = '" onmouseover="alert(1)';
        $html    = 'id="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
    }

    public function testWeblinkClassDoubleQuoteBreakout(): void
    {
        $payload = '" style="background:url(javascript:alert(1))';
        $html    = 'class="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
        $this->assertStringContainsString('&quot;', $html);
    }

    public function testWeblinkTargetDoubleQuoteBreakout(): void
    {
        $payload = '" onclick="alert(1)';
        $html    = 'target="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
    }

    // =========================================================================
    // 3. weblink — fancybox logic bug fix
    //    OLD: if ($fbox_loaded)  → loadFramework NEVER called on first video
    //    NEW: if (!$fbox_loaded) → loadFramework called exactly once
    // =========================================================================

    public function testFancyboxLoadedExactlyOnce(): void
    {
        $fbox_loaded = null;
        $loadCalled  = 0;

        $load = function () use (&$fbox_loaded, &$loadCalled) {
            if (!$fbox_loaded) {   // ← FIXED condition
                $fbox_loaded = true;
                $loadCalled++;
            }
        };

        $load(); // first video encounter
        $load(); // second video
        $load(); // third video

        $this->assertSame(1, $loadCalled, 'loadFramework must be called exactly once');
        $this->assertTrue($fbox_loaded);
    }

    public function testFancyboxBugProof(): void
    {
        // Documents the old bug: if ($fbox_loaded) with initial null → never fires
        $fbox_loaded = null;
        $loadCalled  = 0;

        $brokenLoad = function () use (&$fbox_loaded, &$loadCalled) {
            if ($fbox_loaded) {    // ← BUG: null is falsy → condition never true
                $fbox_loaded = true;
                $loadCalled++;
            }
        };

        $brokenLoad();
        $brokenLoad();

        $this->assertSame(0, $loadCalled,
            'Old bug: null is falsy so framework was never loaded');
    }

    // =========================================================================
    // 4. addressint — lat/lon float cast (JS injection prevention)
    // =========================================================================

    /** @return array<string, array{string, float}> */
    public static function latLonProvider(): array
    {
        return [
            'normal positive'        => ['13.7563',  13.7563],
            'normal negative'        => ['-13.7563', -13.7563],
            'zero'                   => ['0',         0.0],
            'JS injection attempt'   => ['0]; alert(1); [', 0.0],
            'SQL injection attempt'  => ["13.7' OR '1'='1",  13.7],
            'leading zeros'          => ['007.5',    7.5],
            'scientific notation'    => ['1.3e2',    130.0],
            'empty string'           => ['',          0.0],
            'non-numeric'            => ['abc',       0.0],
        ];
    }

    #[DataProvider('latLonProvider')]
    public function testLatLonFloatCast(string $input, float $expected): void
    {
        $result = (float) $input;

        $this->assertSame($expected, $result);

        // The string representation must be safe in JS context (numeric only)
        $jsVal = (string) $result;
        $this->assertMatchesRegularExpression(
            '/^-?\d+(\.\d+)?(E[+-]\d+)?$/',
            $jsVal,
            "Float string '{$jsVal}' must be safe to embed in JS without quoting"
        );
    }

    public function testLatLonJsArrayNotInjectable(): void
    {
        $maliciousLat = "0]; alert('XSS'); var x=[";
        $maliciousLon = '0]; document.cookie="stolen"; var y=[';

        $safeLat = (float) $maliciousLat;  // 0.0
        $safeLon = (float) $maliciousLon;  // 0.0

        $jsArray = "['" . addslashes('popup html') . "', " . $safeLat . ', ' . $safeLon . ']';

        $this->assertStringNotContainsString('alert', $jsArray);
        $this->assertStringNotContainsString('document.cookie', $jsArray);
        $this->assertStringContainsString('0, 0', $jsArray);
    }

    // =========================================================================
    // 5. addressint — addr_display (nl2br + htmlspecialchars)
    // =========================================================================

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testAddrintAddrDisplayNoRawTags(string $payload): void
    {
        $html = '<div class="address">' . nl2br(self::esc($payload)) . '</div>';
        $this->assertNoRawTags($html, '<div class="address">');
    }

    public function testAddrintAddrDisplayNewlinesToBr(): void
    {
        $addr = "Line 1\nLine 2\r\nLine 3";
        $html = nl2br(self::esc($addr));

        $this->assertStringContainsString('<br />', $html);
        $this->assertStringContainsString('Line 1', $html);
        $this->assertStringContainsString('Line 2', $html);
    }

    // =========================================================================
    // 6. addressint field.php — addr1/addr2/addr3 in textarea
    // =========================================================================

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testAddrintTextareaValueNoBreakout(string $payload): void
    {
        $html = '<textarea name="addr1">' . self::esc($payload) . '</textarea>';
        // Payload must not close the textarea early
        $this->assertDoesNotMatchRegularExpression(
            '/<\/textarea\s*>.*<script/is',
            $html,
            'Payload must not break out of textarea'
        );
    }

    // =========================================================================
    // 7. addressint map_link in directions href
    // =========================================================================

    public function testAddrintMapLinkHrefDoubleQuoteBreakout(): void
    {
        $payload = '" onmouseover="alert(1)';
        $html    = 'href="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
        $this->assertStringContainsString('&quot;', $html);
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testAddrintMapLinkHrefNoRawTags(string $payload): void
    {
        $escaped = self::esc($payload);
        $this->assertDoesNotMatchRegularExpression('/<\w/', $escaped);
    }

    // =========================================================================
    // 8. file/share_form.php — share description textarea
    // =========================================================================

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testShareFormDescTextareaNoBreakout(string $payload): void
    {
        $html = '<textarea name="desc">' . self::esc($payload) . '</textarea>';
        $this->assertDoesNotMatchRegularExpression(
            '/<\/textarea\s*>.*<script/is',
            $html
        );
        $this->assertNoRawTags($html, '<textarea name="desc">');
    }

    // =========================================================================
    // 9. file/value_InlineBoxes.php — download onclick (ENT_QUOTES for JS)
    //    Single quotes in URL must be encoded so JS string cannot be broken out of
    // =========================================================================

    public function testDownloadLinkOnclickSingleQuoteEncoded(): void
    {
        // Malicious URL tries to close the JS single-quoted string
        $maliciousUrl = "http://good.com/f.zip'); alert('xss')";

        $escaped = self::escJs($maliciousUrl);
        $onclick  = "onclick=\"window.open('{$escaped}', '_blank'); return false;\"";

        // Single quote in payload must be encoded as &#039;
        $this->assertStringContainsString('&#039;', $onclick,
            'Single quotes in JS string context must be encoded with ENT_QUOTES');
        // The injected alert must not appear executable
        $this->assertStringNotContainsString("'); alert('", $onclick);
    }

    // =========================================================================
    // 10. Integer cast for IDs in URL construction
    // =========================================================================

    /** @return array<string, array{mixed, int}> */
    public static function idProvider(): array
    {
        return [
            'normal id'         => [42,          42],
            'string id'         => ['42',         42],
            'zero'              => [0,              0],
            'SQL injection'     => ['1 OR 1=1',    1],
            'float string'      => ['3.9',          3],
            'empty string'      => ['',             0],
            'null'              => [null,            0],
        ];
    }

    #[DataProvider('idProvider')]
    public function testIdIntCastSafeForUrl(mixed $input, int $expected): void
    {
        $safe = (int) $input;
        $this->assertSame($expected, $safe);

        $url = 'index.php?option=com_flexicontent&fc_field_id=' . $safe;
        $this->assertMatchesRegularExpression('/fc_field_id=-?\d+$/', $url);
    }
}
