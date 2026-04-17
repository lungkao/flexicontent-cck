<?php
/**
 * FcGridHelper — Grid layout resolution for FLEXIcontent card templates.
 *
 * Centralises the "how many columns for N featured items in style X" logic
 * that is also mirrored in the CSS :has() rules and fc-card-anim.js fallback.
 * Having it here makes the rule authoritative and unit-testable.
 */
class FcGridHelper
{
    /* Breakpoint constants (px) — must match CSS @media and JS vw checks */
    const BP_MOBILE = 640;
    const BP_TABLET = 768;

    /**
     * Returns the CSS grid-template-columns value for a featured block.
     *
     * @param  int    $total   Number of featured items (data-total attribute)
     * @param  string $style   Card style: hero | overlay | minimal
     * @param  int    $vw      Viewport width in px (pass PHP_INT_MAX for desktop)
     * @return string          CSS value, e.g. '1fr 1fr' or 'repeat(3,1fr)'
     */
    public static function featuredGridCols(int $total, string $style = 'hero', int $vw = PHP_INT_MAX): string
    {
        /* Mobile: always single column */
        if ($vw <= self::BP_MOBILE) {
            return '1fr';
        }

        $isTablet  = $vw <= self::BP_TABLET;
        $isOverlay = ($style === 'overlay');
        $isMinimal = ($style === 'minimal');

        switch ($total) {
            case 1:
                return '1fr';

            case 2:
                /* All styles: 2-col side by side */
                return '1fr 1fr';

            case 3:
                if ($isOverlay || $isMinimal) {
                    /* overlay/minimal: equal 3-col on desktop, 2-col on tablet */
                    return $isTablet ? '1fr 1fr' : 'repeat(3,1fr)';
                }
                /* hero/default: magazine (1fr 1fr, first item spans full via grid-column) */
                return '1fr 1fr';

            case 4:
            case 5:
                if ($isOverlay || $isMinimal) {
                    return $isTablet ? '1fr 1fr' : 'repeat(4,1fr)';
                }
                /* hero/default: 3-col on desktop, 2-col on tablet */
                return $isTablet ? '1fr 1fr' : 'repeat(3,1fr)';

            default:
                /* 6+ items: same as 4/5 */
                if ($isOverlay || $isMinimal) {
                    return $isTablet ? '1fr 1fr' : 'repeat(4,1fr)';
                }
                return $isTablet ? '1fr 1fr' : 'repeat(3,1fr)';
        }
    }

    /**
     * Returns whether the first item (data-index="0") should span all columns.
     *
     * @param  int    $total  Number of featured items
     * @param  string $style  Card style
     * @param  int    $vw     Viewport width in px
     * @return bool
     */
    public static function firstItemSpansAll(int $total, string $style = 'hero', int $vw = PHP_INT_MAX): bool
    {
        if ($vw <= self::BP_MOBILE) {
            return false; /* mobile: 1-col, spanning is meaningless */
        }
        if ($style === 'overlay') {
            return false; /* overlay resets grid-column to unset */
        }
        /* hero/default/minimal: first item spans all cols when total >= 3 */
        return $total >= 3;
    }

    /**
     * Builds the CSS data-attributes string for a featured block wrapper.
     * Convenience helper for template use.
     *
     * @param  string $style       feat_card_style param value
     * @param  string $imgPosition feat_img_position param value
     * @param  int    $imgWidth    feat_img_width param value (%)
     * @param  int    $minHeight   feat_card_minheight param value (px)
     * @param  string $animation   feat_animation param value
     * @return string              Inline style + data-feat-anim attribute fragment
     */
    public static function featuredBlockAttrs(
        string $style,
        string $imgPosition,
        int $imgWidth,
        int $minHeight,
        string $animation
    ): string {
        $imgWidth  = max(25, min(70, $imgWidth));
        $minHeight = max(180, min(420, $minHeight));

        return sprintf(
            'style="--fc-feat-img-w:%d%%;--fc-feat-minheight:%dpx;" data-feat-anim="%s"',
            $imgWidth,
            $minHeight,
            htmlspecialchars($animation, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Builds CSS class list for a featured block element.
     *
     * @param  string $style       feat_card_style
     * @param  string $imgPosition feat_img_position (used only for hero)
     * @return string              Space-separated class names
     */
    public static function featuredBlockClasses(string $style, string $imgPosition): string
    {
        $classes = ['featured-block', 'fc-items-block', 'fc-feat-style-' . $style];

        if ($style === 'hero') {
            $classes[] = 'fc-feat-img-' . $imgPosition;
        }

        return implode(' ', $classes);
    }
}
