<?php
/**
 * FLEXIcontent Performance Helper
 * - Query result caching
 * - Asset optimization utilities
 * - Lazy loading helpers
 * @version 6.1.0
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;

class FlexiPerformance
{
    /** @var array In-memory query cache */
    protected static array $_mem_cache = [];

    /** @var int Default cache TTL (seconds) */
    const CACHE_TTL = 900; // 15 minutes

    /**
     * Get/set cached DB result
     * Usage: $data = FlexiPerformance::cachedQuery($cacheKey, fn() => $db->loadObjectList());
     */
    public static function cachedQuery(string $key, callable $query, int $ttl = self::CACHE_TTL): mixed
    {
        // 1. In-memory cache (same request)
        if (isset(self::$_mem_cache[$key])) {
            return self::$_mem_cache[$key];
        }

        // 2. Joomla cache (cross-request)
        if (!JDEBUG) {
            try {
                $app   = Factory::getApplication();
                $cache = $app->get('cachecontroller', null);
                
                /** @var \Joomla\CMS\Cache\Controller\CallbackController $cb */
                $cb = Factory::getContainer()
                    ->get(CacheControllerFactoryInterface::class)
                    ->createCacheController('callback', [
                        'defaultgroup' => 'com_flexicontent',
                        'cachebase'    => $app->get('cache_path', JPATH_CACHE),
                        'lifetime'     => $ttl / 60,
                        'language'     => $app->get('language', 'en-GB'),
                        'storage'      => $app->get('cache_handler', 'file'),
                    ]);

                $result = $cb->get($query, [], $key);
            } catch (\Throwable $e) {
                // Fallback to direct query on cache error
                $result = $query();
            }
        } else {
            $result = $query();
        }

        self::$_mem_cache[$key] = $result;
        return $result;
    }

    /**
     * Clear FLEXIcontent cache group
     */
    public static function clearCache(): void
    {
        try {
            $app   = Factory::getApplication();
            $cache = Factory::getContainer()
                ->get(CacheControllerFactoryInterface::class)
                ->createCacheController('callback', [
                    'defaultgroup' => 'com_flexicontent',
                    'cachebase'    => $app->get('cache_path', JPATH_CACHE),
                    'lifetime'     => self::CACHE_TTL / 60,
                    'storage'      => $app->get('cache_handler', 'file'),
                ]);
            $cache->clean('com_flexicontent');
        } catch (\Throwable $e) {
            // ignore
        }
        self::$_mem_cache = [];
    }

    /**
     * Get image tag with lazy loading + WebP support
     * 
     * @param string $src      Image URL
     * @param string $alt      Alt text
     * @param array  $attrs    Extra HTML attributes
     * @param bool   $eager    Force eager loading (above fold)
     * @return string HTML img tag
     */
    public static function lazyImg(string $src, string $alt = '', array $attrs = [], bool $eager = false): string
    {
        $loading = $eager ? 'eager' : 'lazy';
        $decode  = $eager ? 'sync' : 'async';

        // Try WebP version if exists
        $webp_src = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);
        if ($webp_src !== $src && file_exists(JPATH_SITE . '/' . ltrim(parse_url($webp_src, PHP_URL_PATH), '/'))) {
            // Use <picture> for WebP with fallback
            $attr_str = '';
            foreach ($attrs as $k => $v) {
                $attr_str .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
            }
            $loading_attrs = ' loading="' . $loading . '" decoding="' . $decode . '"';
            return '<picture>'
                . '<source srcset="' . htmlspecialchars($webp_src) . '" type="image/webp">'
                . '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '"'
                . $loading_attrs . $attr_str . '>'
                . '</picture>';
        }

        // Standard img tag with lazy loading
        $attr_str = ' loading="' . $loading . '" decoding="' . $decode . '"';
        foreach ($attrs as $k => $v) {
            $attr_str .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
        }

        return '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '"' . $attr_str . '>';
    }

    /**
     * Generate responsive srcset for images
     *
     * @param string $src    Base image URL
     * @param array  $sizes  Array of [width => url] or just widths for auto-suffix
     * @return string srcset attribute value
     */
    public static function srcset(string $src, array $sizes = [320, 640, 1024, 1280]): string
    {
        $parts = [];
        $ext   = pathinfo($src, PATHINFO_EXTENSION);
        $base  = str_replace('.' . $ext, '', $src);

        foreach ($sizes as $w) {
            $candidate = $base . '-' . $w . 'w.' . $ext;
            $full_path = JPATH_SITE . '/' . ltrim(parse_url($candidate, PHP_URL_PATH), '/');
            if (file_exists($full_path)) {
                $parts[] = htmlspecialchars($candidate) . ' ' . $w . 'w';
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Preload critical CSS/JS resource hint
     */
    public static function preloadHint(string $url, string $as = 'style', ?string $type = null): void
    {
        $doc  = Factory::getDocument();
        $hint = '<link rel="preload" href="' . htmlspecialchars($url) . '" as="' . $as . '"';
        if ($type) $hint .= ' type="' . htmlspecialchars($type) . '"';
        if ($as === 'font') $hint .= ' crossorigin="anonymous"';
        $hint .= '>';
        $doc->addCustomTag($hint);
    }

    /**
     * DNS prefetch hint for external domains
     */
    public static function dnsPrefetch(string ...$domains): void
    {
        $doc = Factory::getDocument();
        foreach ($domains as $domain) {
            $doc->addCustomTag('<link rel="dns-prefetch" href="' . htmlspecialchars($domain) . '">');
            $doc->addCustomTag('<link rel="preconnect" href="' . htmlspecialchars($domain) . '" crossorigin>');
        }
    }

    /**
     * Check if request is from mobile device
     */
    public static function isMobile(): bool
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $ua);
    }

    /**
     * Get asset URL with version hash for cache busting
     */
    public static function assetUrl(string $path, bool $minified = true): string
    {
        $base = \Joomla\CMS\Uri\Uri::root();
        if ($minified && !JDEBUG) {
            $path = preg_replace('/\.(css|js)$/', '.min.$1', $path);
        }
        return $base . ltrim($path, '/') . '?v=' . FLEXI_VHASH;
    }
}
