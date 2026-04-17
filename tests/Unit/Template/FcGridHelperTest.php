<?php
/**
 * FcGridHelperTest — Unit tests for FcGridHelper.
 *
 * Tests the authoritative grid-column resolution logic that is mirrored by:
 *   - CSS :has([data-total="N"]) rules in flexi_frontend_modern.css
 *   - applyGridFallback() in site/assets/js/fc-card-anim.js
 *
 * Run: cd tests && vendor/bin/phpunit Unit/Template/FcGridHelperTest.php
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once dirname(__DIR__, 2) . '/../site/helpers/FcGridHelper.php';

class FcGridHelperTest extends TestCase
{
    /* ═══════════════════════════════════════════════════════════════
       featuredGridCols() — desktop (vw = PHP_INT_MAX)
       ═══════════════════════════════════════════════════════════════ */

    #[DataProvider('desktopGridColsProvider')]
    public function testFeaturedGridColsDesktop(int $total, string $style, string $expected): void
    {
        $this->assertSame(
            $expected,
            FcGridHelper::featuredGridCols($total, $style),
            "total={$total} style={$style} desktop"
        );
    }

    public static function desktopGridColsProvider(): array
    {
        return [
            /* ── total=1: always full width ──────────────────── */
            'hero total=1'    => [1, 'hero',    '1fr'],
            'overlay total=1' => [1, 'overlay', '1fr'],
            'minimal total=1' => [1, 'minimal', '1fr'],

            /* ── total=2: always 2-col side-by-side ──────────── */
            'hero total=2'    => [2, 'hero',    '1fr 1fr'],
            'overlay total=2' => [2, 'overlay', '1fr 1fr'],
            'minimal total=2' => [2, 'minimal', '1fr 1fr'],

            /* ── total=3 ─────────────────────────────────────── */
            'hero total=3'    => [3, 'hero',    '1fr 1fr'],     // magazine
            'overlay total=3' => [3, 'overlay', 'repeat(3,1fr)'],
            'minimal total=3' => [3, 'minimal', 'repeat(3,1fr)'],

            /* ── total=4 ─────────────────────────────────────── */
            'hero total=4'    => [4, 'hero',    'repeat(3,1fr)'],
            'overlay total=4' => [4, 'overlay', 'repeat(4,1fr)'],
            'minimal total=4' => [4, 'minimal', 'repeat(4,1fr)'],

            /* ── total=5 (same bucket as 4) ──────────────────── */
            'hero total=5'    => [5, 'hero',    'repeat(3,1fr)'],
            'overlay total=5' => [5, 'overlay', 'repeat(4,1fr)'],

            /* ── total=6+ (>5 items) ─────────────────────────── */
            'hero total=6'    => [6, 'hero',    'repeat(3,1fr)'],
            'overlay total=8' => [8, 'overlay', 'repeat(4,1fr)'],
        ];
    }

    /* ═══════════════════════════════════════════════════════════════
       featuredGridCols() — tablet (641 < vw ≤ 768)
       ═══════════════════════════════════════════════════════════════ */

    #[DataProvider('tabletGridColsProvider')]
    public function testFeaturedGridColsTablet(int $total, string $style, string $expected): void
    {
        $this->assertSame(
            $expected,
            FcGridHelper::featuredGridCols($total, $style, 768),
            "total={$total} style={$style} tablet(768)"
        );
    }

    public static function tabletGridColsProvider(): array
    {
        return [
            /* overlay/minimal collapse to 2-col at tablet */
            'overlay total=3 tablet' => [3, 'overlay', '1fr 1fr'],
            'minimal total=3 tablet' => [3, 'minimal', '1fr 1fr'],
            'overlay total=4 tablet' => [4, 'overlay', '1fr 1fr'],
            'minimal total=4 tablet' => [4, 'minimal', '1fr 1fr'],
            /* hero: total=2 stays 2-col */
            'hero total=2 tablet'    => [2, 'hero',    '1fr 1fr'],
            /* hero: total=4 collapses to 2-col at tablet */
            'hero total=4 tablet'    => [4, 'hero',    '1fr 1fr'],
        ];
    }

    /* ═══════════════════════════════════════════════════════════════
       featuredGridCols() — mobile (vw ≤ 640)
       ═══════════════════════════════════════════════════════════════ */

    public function testFeaturedGridColsMobileAlwaysSingleColumn(): void
    {
        foreach (['hero', 'overlay', 'minimal'] as $style) {
            foreach ([1, 2, 3, 4, 5, 6] as $total) {
                $this->assertSame(
                    '1fr',
                    FcGridHelper::featuredGridCols($total, $style, 640),
                    "mobile should always be 1fr — total={$total} style={$style}"
                );
            }
        }
    }

    public function testMobileBoundaryAt640px(): void
    {
        /* vw=640 is mobile → 1fr */
        $this->assertSame('1fr', FcGridHelper::featuredGridCols(4, 'hero', 640));
        /* vw=641 is NOT mobile → resolves normally */
        $this->assertSame('1fr 1fr', FcGridHelper::featuredGridCols(4, 'hero', 641));
    }

    public function testTabletBoundaryAt768px(): void
    {
        /* vw=768 → tablet → overlay collapses */
        $this->assertSame('1fr 1fr', FcGridHelper::featuredGridCols(4, 'overlay', 768));
        /* vw=769 → desktop → overlay uses repeat(4,1fr) */
        $this->assertSame('repeat(4,1fr)', FcGridHelper::featuredGridCols(4, 'overlay', 769));
    }

    /* ═══════════════════════════════════════════════════════════════
       firstItemSpansAll()
       ═══════════════════════════════════════════════════════════════ */

    #[DataProvider('firstItemSpanProvider')]
    public function testFirstItemSpansAll(int $total, string $style, int $vw, bool $expected): void
    {
        $this->assertSame(
            $expected,
            FcGridHelper::firstItemSpansAll($total, $style, $vw),
            "firstItemSpansAll total={$total} style={$style} vw={$vw}"
        );
    }

    public static function firstItemSpanProvider(): array
    {
        return [
            /* hero: spans when total >= 3 */
            'hero total=2 desktop' => [2, 'hero',    PHP_INT_MAX, false],
            'hero total=3 desktop' => [3, 'hero',    PHP_INT_MAX, true],
            'hero total=4 desktop' => [4, 'hero',    PHP_INT_MAX, true],

            /* overlay: NEVER spans (CSS L1395-1398 resets to unset) */
            'overlay total=3'      => [3, 'overlay', PHP_INT_MAX, false],
            'overlay total=4'      => [4, 'overlay', PHP_INT_MAX, false],

            /* minimal: spans when total >= 3 */
            'minimal total=3'      => [3, 'minimal', PHP_INT_MAX, true],

            /* mobile: never spans (1-col anyway) */
            'hero total=4 mobile'  => [4, 'hero',    640, false],
        ];
    }

    /* ═══════════════════════════════════════════════════════════════
       featuredBlockAttrs() — HTML attribute string
       ═══════════════════════════════════════════════════════════════ */

    public function testFeaturedBlockAttrsDefaultValues(): void
    {
        $result = FcGridHelper::featuredBlockAttrs('hero', 'left', 50, 280, 'fade-up');
        $this->assertStringContainsString('--fc-feat-img-w:50%', $result);
        $this->assertStringContainsString('--fc-feat-minheight:280px', $result);
        $this->assertStringContainsString('data-feat-anim="fade-up"', $result);
    }

    public function testFeaturedBlockAttrsClampImageWidth(): void
    {
        /* Values outside 25-70% must be clamped */
        $tooSmall = FcGridHelper::featuredBlockAttrs('hero', 'left', 10, 280, 'fade-up');
        $this->assertStringContainsString('--fc-feat-img-w:25%', $tooSmall);

        $tooBig = FcGridHelper::featuredBlockAttrs('hero', 'left', 99, 280, 'fade-up');
        $this->assertStringContainsString('--fc-feat-img-w:70%', $tooBig);
    }

    public function testFeaturedBlockAttrsClampMinHeight(): void
    {
        /* Values outside 180-420px must be clamped */
        $tooSmall = FcGridHelper::featuredBlockAttrs('hero', 'left', 50, 50, 'fade-up');
        $this->assertStringContainsString('--fc-feat-minheight:180px', $tooSmall);

        $tooBig = FcGridHelper::featuredBlockAttrs('hero', 'left', 50, 9999, 'fade-up');
        $this->assertStringContainsString('--fc-feat-minheight:420px', $tooBig);
    }

    public function testFeaturedBlockAttrsEscapesAnimation(): void
    {
        $result = FcGridHelper::featuredBlockAttrs('hero', 'left', 50, 280, '<script>xss</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /* ═══════════════════════════════════════════════════════════════
       featuredBlockClasses()
       ═══════════════════════════════════════════════════════════════ */

    public function testFeaturedBlockClassesHero(): void
    {
        $classes = FcGridHelper::featuredBlockClasses('hero', 'left');
        $this->assertStringContainsString('featured-block', $classes);
        $this->assertStringContainsString('fc-items-block', $classes);
        $this->assertStringContainsString('fc-feat-style-hero', $classes);
        $this->assertStringContainsString('fc-feat-img-left', $classes);
    }

    public function testFeaturedBlockClassesOverlayHasNoImgPosition(): void
    {
        $classes = FcGridHelper::featuredBlockClasses('overlay', 'left');
        $this->assertStringNotContainsString('fc-feat-img-', $classes,
            'Only hero style should have img-position class');
        $this->assertStringContainsString('fc-feat-style-overlay', $classes);
    }

    public function testFeaturedBlockClassesAllPositions(): void
    {
        foreach (['left', 'right', 'top', 'bottom', 'between'] as $pos) {
            $classes = FcGridHelper::featuredBlockClasses('hero', $pos);
            $this->assertStringContainsString('fc-feat-img-' . $pos, $classes);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
       Consistency: JS fallback must match PHP logic
       (Documents the contract between FcGridHelper and fc-card-anim.js)
       ═══════════════════════════════════════════════════════════════ */

    /**
     * Regression guard: if FcGridHelper returns 'repeat(3,1fr)' for hero+4 items,
     * the JS fallback MUST also set gridTemplateColumns='repeat(3,1fr)'.
     * If this test breaks, update fc-card-anim.js to match.
     */
    public function testDesktopHeroFourItemsIsThreeColumns(): void
    {
        $this->assertSame(
            'repeat(3,1fr)',
            FcGridHelper::featuredGridCols(4, 'hero'),
            'JS fallback uses repeat(3,1fr) for hero+4 — must stay in sync'
        );
    }

    public function testDesktopOverlayThreeItemsIsThreeColumns(): void
    {
        $this->assertSame(
            'repeat(3,1fr)',
            FcGridHelper::featuredGridCols(3, 'overlay'),
            'JS fallback uses repeat(3,1fr) for overlay+3 — must stay in sync'
        );
    }
}
