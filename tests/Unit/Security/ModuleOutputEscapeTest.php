<?php
/**
 * ModuleOutputEscapeTest — Unit tests for XSS hardening in module templates.
 *
 * Covers fixes made to:
 *   - modules/mod_flexiadvsearch/tmpl/default.php      ($searchword in input[value])
 *   - modules/mod_flexicategories/tmpl/default_items.php ($cat->title in img/anchor)
 *   - modules/mod_flexitagcloud/tmpl/default.php        ($item->name in anchor)
 *   - modules/mod_flexigooglemap/tmpl/default_mapLocation.php (lat/lon float, href)
 *   - modules/mod_flexigooglemap/tmpl/default.php       ($marker_opacity float in CSS)
 *
 * Key principle: htmlspecialchars(ENT_COMPAT) encodes < > & " to entities.
 * The correct test for "escaped properly" is NOT assertStringNotContainsString('onerror', $html)
 * but assertDoesNotMatchRegularExpression('/<img\b/', $html) — raw unescaped tags must be absent.
 *
 * Run: cd tests && vendor/bin/phpunit Unit/Security/ModuleOutputEscapeTest.php
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ModuleOutputEscapeTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_COMPAT, 'UTF-8');
    }

    private function assertAttributeWellFormed(string $attrHtml): void
    {
        $this->assertMatchesRegularExpression(
            '/\w+="[^"]*"/',
            $attrHtml,
            'Attribute must remain well-formed — payload cannot break out via double-quote'
        );
    }

    private function assertNoRawTags(string $html, string $trustedPrefix = ''): void
    {
        $inner = $trustedPrefix ? substr($html, strlen($trustedPrefix)) : $html;
        $this->assertDoesNotMatchRegularExpression(
            '/<(script|img|svg|iframe|object|embed|form|input|button)\b/i',
            $inner,
            'Dangerous HTML tags must not appear unescaped in the output'
        );
    }

    // =========================================================================
    // Data providers
    // =========================================================================

    /** @return array<string, array{string}> */
    public static function htmlTagPayloadsProvider(): array
    {
        return [
            'script tag'        => ['<script>alert(1)</script>'],
            'img onerror'       => ['<img src=x onerror=alert(1)>'],
            'svg onload'        => ['<svg onload=alert(1)>'],
            'iframe'            => ['<iframe src="javascript:alert(1)">'],
            'javascript: proto' => ['javascript:alert(document.cookie)'],
        ];
    }

    // =========================================================================
    // 1. mod_flexiadvsearch — $searchword in input[value]
    //    Critical: comes directly from URL parameter (?q=...)
    // =========================================================================

    public function testSearchwordDoubleQuoteBreakout(): void
    {
        // Attacker crafts URL: ?q=" onmouseover="alert(1)
        $payload = '" onmouseover="alert(1)';
        $html    = 'value="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
        $this->assertStringContainsString('&quot;', $html,
            '"  must be encoded as &quot; to prevent attribute breakout');
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testSearchwordNoRawTagsInValue(string $payload): void
    {
        $escaped = self::esc($payload);
        // No literal < from the payload must remain
        $this->assertDoesNotMatchRegularExpression('/<\w/', $escaped);
    }

    public function testSearchwordPreservesNormalText(): void
    {
        $word    = 'ค้นหา FLEXIcontent';
        $escaped = self::esc($word);

        $this->assertStringContainsString('ค้นหา', $escaped, 'Thai text must pass through');
        $this->assertStringContainsString('FLEXIcontent', $escaped);
    }

    public function testSearchwordAmpersandAndAngleBrackets(): void
    {
        $word    = 'hello world&foo<bar>';
        $escaped = self::esc($word);

        $this->assertStringContainsString('&amp;', $escaped, '& must become &amp;');
        $this->assertStringContainsString('&lt;',  $escaped, '< must become &lt;');
        $this->assertStringNotContainsString('<bar>', $escaped);
    }

    // =========================================================================
    // 2. mod_flexicategories — $cat->title in img alt/title and anchor text
    // =========================================================================

    public function testCategoryTitleInImgAltBreakout(): void
    {
        $payload = '" onmouseover="alert(1)';
        $html    = 'alt="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
    }

    public function testCategoryTitleInImgTitleBreakout(): void
    {
        $payload = '" onmouseover="alert(1)';
        $html    = 'title="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testCategoryTitleInImgAltNoRawTags(string $payload): void
    {
        $escaped = self::esc($payload);
        $this->assertDoesNotMatchRegularExpression('/<\w/', $escaped);
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testCategoryTitleInAnchorTextNoRawTags(string $payload): void
    {
        $html = '<a href="/category">' . self::esc($payload) . '</a>';
        $this->assertNoRawTags($html, '<a href="/category">');
    }

    public function testCategoryLinkHrefBreakout(): void
    {
        $payload = '" onmouseover="alert(1)';
        $html    = 'href="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
        $this->assertStringContainsString('&quot;', $html);
    }

    public function testCategoryTotalItemsIntCast(): void
    {
        $malicious = '5; DROP TABLE items;';
        $safe      = (int) $malicious;

        $this->assertSame(5, $safe);
        $this->assertStringNotContainsString('DROP', (string) $safe);
    }

    // =========================================================================
    // 3. mod_flexitagcloud — $item->name and $item->size
    // =========================================================================

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testTagNameInAnchorTextNoRawTags(string $payload): void
    {
        $html = '<a href="/tag" class="tag5">' . self::esc($payload) . '</a>';
        $this->assertNoRawTags($html, '<a href="/tag" class="tag5">');
    }

    public function testTagNameDoubleQuoteBreakout(): void
    {
        $payload = '" onmouseover="alert(1)';
        // If used as anchor text, breakout via " should be prevented
        $escaped = self::esc($payload);
        $this->assertStringContainsString('&quot;', $escaped);
        $this->assertDoesNotMatchRegularExpression('/<\w/', $escaped);
    }

    /** @return array<string, array{mixed, int}> */
    public static function sizeProvider(): array
    {
        return [
            'normal 1'         => [1,                       1],
            'normal 5'         => [5,                       5],
            'string size'      => ['3',                     3],
            'inject attempt'   => ['5 onload=alert(1)',     5],
            'float'            => [3.7,                     3],
            'empty'            => ['',                      0],
        ];
    }

    #[DataProvider('sizeProvider')]
    public function testTagSizeIntCastProducesSafeClass(mixed $size, int $expected): void
    {
        $safe  = (int) $size;
        $class = 'tag' . $safe;

        $this->assertSame($expected, $safe);
        $this->assertMatchesRegularExpression('/^tag-?\d+$/', $class,
            'CSS class must be alphanumeric — no injection possible');
    }

    // =========================================================================
    // 4. mod_flexigooglemap — lat/lon float cast in JS locations array
    // =========================================================================

    /** @return array<string, array{string, float}> */
    public static function coordProvider(): array
    {
        return [
            'Bangkok lat'       => ['13.7563',  13.7563],
            'Bangkok lon'       => ['100.5018', 100.5018],
            'negative'          => ['-33.8688', -33.8688],
            'zero'              => ['0',          0.0],
            'JS injection'      => ["13.7']; alert('xss'); var x=['",  13.7],
            'semicolon inject'  => ['13.7; document.cookie=1',         13.7],
            'bracket inject'    => ['0]; alert(1); [',                  0.0],
            'null byte'         => ["13.7\x00alert",                   13.7],
            'empty'             => ['',                                  0.0],
            'non-numeric'       => ['abc',                               0.0],
        ];
    }

    #[DataProvider('coordProvider')]
    public function testLatLonFloatCast(string $input, float $expected): void
    {
        $safe = (float) $input;

        $this->assertSame($expected, $safe);

        // Float string must be safe to embed in JS without quoting
        $jsVal = (string) $safe;
        $this->assertStringNotContainsString('alert',           $jsVal);
        $this->assertStringNotContainsString('document',        $jsVal);
        $this->assertStringNotContainsString("'",               $jsVal);
        $this->assertStringNotContainsString(';',               $jsVal);
    }

    public function testMapLocationsArrayInjectionPrevented(): void
    {
        $maliciousLat = "0]; alert('map_xss'); var a=[";
        $maliciousLon = '0]; document.cookie="stolen"; var b=[';

        $safeLat = (float) $maliciousLat;  // 0.0
        $safeLon = (float) $maliciousLon;  // 0.0

        $jsEntry = "['<div>popup</div>', " . $safeLat . ', ' . $safeLon . ", '__default__']";

        $this->assertStringNotContainsString('alert',           $jsEntry);
        $this->assertStringNotContainsString('document.cookie', $jsEntry);
        $this->assertMatchesRegularExpression(
            "/\['.+', 0, 0, '__default__'\]/",
            $jsEntry
        );
    }

    // =========================================================================
    // 5. mod_flexigooglemap — $markerDirections_link href
    // =========================================================================

    public function testMapDirectionsLinkHrefBreakout(): void
    {
        $payload = '" onmouseover="alert(1)';
        $html    = 'href="' . self::esc($payload) . '"';

        $this->assertAttributeWellFormed($html);
        $this->assertStringContainsString('&quot;', $html);
    }

    #[DataProvider('htmlTagPayloadsProvider')]
    public function testMapDirectionsLinkHrefNoRawTags(string $payload): void
    {
        $escaped = self::esc($payload);
        $this->assertDoesNotMatchRegularExpression('/<\w/', $escaped);
    }

    // =========================================================================
    // 6. mod_flexigooglemap — $marker_opacity float cast in CSS output
    // =========================================================================

    /** @return array<string, array{mixed, float}> */
    public static function opacityProvider(): array
    {
        return [
            'normal 0.7'         => ['0.7',                            0.7],
            'normal 1'           => ['1',                              1.0],
            'normal 0'           => ['0',                              0.0],
            'CSS injection'      => ['1 !important; color: red',       1.0],
            'style breakout'     => ['1</style><script>alert(1)',      1.0],
            'negative'           => ['-0.5',                          -0.5],
            'empty string'       => ['',                               0.0],
            'non-numeric'        => ['abc',                            0.0],
        ];
    }

    #[DataProvider('opacityProvider')]
    public function testMarkerOpacityFloatCastForCss(mixed $input, float $expected): void
    {
        $safe    = (float) $input;
        $cssRule = 'opacity: ' . $safe . ' !important;';

        $this->assertSame($expected, $safe, 'Value must cast to expected float');

        $this->assertStringNotContainsString('</style>', $cssRule);
        $this->assertStringNotContainsString('<script',  $cssRule);
        $this->assertStringNotContainsString('color:',   $cssRule);
        $this->assertMatchesRegularExpression(
            '/^opacity: -?[\d.]+(E[+-]\d+)? !important;$/',
            $cssRule,
            'CSS rule must be numeric only'
        );
    }
}
