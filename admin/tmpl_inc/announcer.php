<?php
/**
 * FLEXIcontent shared SR announcer (B7 — Live Regions).
 *
 * Renders one polite + one assertive live region near the bottom of
 * the rendered page plus a global JS helper `flexicontent.announce()`.
 *
 * Usage in a view template:
 *   require_once JPATH_ADMINISTRATOR
 *       . '/components/com_flexicontent/tmpl_inc/announcer.php';
 *
 * Then in JS:
 *   flexicontent.announce('Saved');
 *   flexicontent.announce('Validation failed', { assertive: true });
 *
 * Idempotent — including the file multiple times in one page renders
 * the markup only once.
 *
 * @package  FLEXIcontent
 * @since    6.1.0
 */
defined('_JEXEC') or die('Restricted access');

if (defined('FLEXI_ANNOUNCER_RENDERED')) {
	return;
}
define('FLEXI_ANNOUNCER_RENDERED', 1);
?>
<div id="fc-announcer-polite"    class="visually-hidden" role="status" aria-live="polite"    aria-atomic="true"></div>
<div id="fc-announcer-assertive" class="visually-hidden" role="alert"  aria-live="assertive" aria-atomic="true"></div>
<script>
(function (w) {
	if (w.flexicontent && typeof w.flexicontent.announce === 'function') {
		return;
	}
	w.flexicontent = w.flexicontent || {};
	w.flexicontent.announce = function (msg, opts) {
		if (!msg) { return; }
		var assertive = !!(opts && opts.assertive);
		var elId = assertive ? 'fc-announcer-assertive' : 'fc-announcer-polite';
		var el = document.getElementById(elId);
		if (!el) { return; }
		// Clear then set on next tick so assistive tech re-announces
		// identical strings (otherwise no change == no announcement).
		el.textContent = '';
		w.setTimeout(function () { el.textContent = String(msg); }, 30);
	};
})(window);
</script>
