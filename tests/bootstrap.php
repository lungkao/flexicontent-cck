<?php
/**
 * PHPUnit bootstrap — standalone (no Joomla CMS required for unit tests).
 * Stubs the minimal Joomla constants and classes needed by FLEXIcontent helpers.
 */

defined('_JEXEC')                || define('_JEXEC', 1);
defined('JPATH_ROOT')            || define('JPATH_ROOT', '/Users/pisan/Sites/joomla54');
defined('JPATH_BASE')            || define('JPATH_BASE', JPATH_ROOT);
defined('JPATH_SITE')            || define('JPATH_SITE', JPATH_ROOT);
defined('JPATH_ADMINISTRATOR')   || define('JPATH_ADMINISTRATOR', JPATH_ROOT . '/administrator');
defined('JPATH_COMPONENT_SITE')  || define('JPATH_COMPONENT_SITE', JPATH_ROOT . '/components/com_flexicontent');
defined('DS')                    || define('DS', DIRECTORY_SEPARATOR);

/* Stub JText / Text so template helpers that call Text::_() don't crash */
if (!class_exists('Joomla\CMS\Language\Text', false)) {
    eval('namespace Joomla\CMS\Language; class Text { public static function _($s) { return $s; } }');
}

/* Autoload stubs */
spl_autoload_register(function (string $class): void {
    $stubFile = __DIR__ . '/stubs/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($stubFile)) {
        require_once $stubFile;
    }
});

/* Source autoload — PSR-4 style for site/helpers */
spl_autoload_register(function (string $class): void {
    $base = dirname(__DIR__) . '/site/helpers/';
    $file = $base . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
