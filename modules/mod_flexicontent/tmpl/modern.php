<?php
/**
 * @package         FLEXIcontent
 * @subpackage      mod_flexicontent / modern template
 *
 * @author          FLEXIcontent Team
 * @link            https://flexicontent.org
 * @copyright       Copyright © 2026, FLEXIcontent team, All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 * Modern grid template — CSS Grid, minimal params, Pro Theme–ready.
 * Available variables (set by mod_flexicontent.php):
 *   $params        — module params (Joomla Registry)
 *   $list          — items for current category: $list[$ord]['featured'] + ['standard']
 *   $ordering      — array of ordering group names
 *   $layout        — 'modern'
 *   $moduleclass_sfx
 *   $module        — module object
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\String\StringHelper;

// ── Layout params ────────────────────────────────────────────────────────────
$mode        = $params->get('modern_mode', 'card');       // card | list | compact
$cols_d      = (int) $params->get('modern_cols_desktop', 3);
$cols_t      = (int) $params->get('modern_cols_tablet',  2);
$cols_m      = (int) $params->get('modern_cols_mobile',  1);
$gap         = $params->get('modern_gap', 'md');          // none|sm|md|lg
$img_ratio   = $params->get('modern_img_ratio', '56.25'); // % or 0 = free

// ── Content toggles ──────────────────────────────────────────────────────────
$show_image    = (int) $params->get('modern_show_image',    1);
$show_category = (int) $params->get('modern_show_category', 1);
$show_date     = $params->get('modern_show_date', 'created'); // ''|created|modified|publish_up
$show_text     = (int) $params->get('modern_show_text',     1);
$text_chars    = (int) $params->get('modern_text_chars',    120);
$title_chars   = (int) $params->get('modern_title_chars',   80);
$show_readmore = (int) $params->get('modern_show_readmore',  1);
$readmore_lbl  = htmlspecialchars($params->get('modern_readmore_label', 'Read more →'));

// ── Image params ─────────────────────────────────────────────────────────────
$img_w = (int) $params->get('modern_img_width',  400);
$img_h = (int) $params->get('modern_img_height', 225);

// ── Style params ─────────────────────────────────────────────────────────────
$card_shadow = (int) $params->get('modern_card_shadow', 1);
$card_hover  = (int) $params->get('modern_card_hover',  1);
$custom_cls  = htmlspecialchars(trim($params->get('modern_custom_class', '')));

// ── Build flat item list (featured first, then standard) ────────────────────
$all_items = [];
foreach ($ordering as $ord) {
	if (!empty($list[$ord]['featured'])) {
		foreach ($list[$ord]['featured'] as $item) {
			$all_items[] = $item;
		}
	}
	if (!empty($list[$ord]['standard'])) {
		foreach ($list[$ord]['standard'] as $item) {
			$all_items[] = $item;
		}
	}
}

if (empty($all_items)) {
	return;
}

// ── Unique ID for multi-module on same page ───────────────────────────────────
$uid = 'fcmod-' . $module->id;

// ── CSS custom properties (set inline for easy override) ─────────────────────
$ratio_css  = $img_ratio > 0 ? '--fcmod-img-ratio:' . (float)$img_ratio . '%;' : '';
$inline_css = implode('', [
	"--fcmod-cols:{$cols_d};",
	"--fcmod-cols-t:{$cols_t};",
	"--fcmod-cols-m:{$cols_m};",
	$ratio_css,
]);

// ── Wrapper classes ───────────────────────────────────────────────────────────
$wrapper_classes = implode(' ', array_filter([
	'fcmod-modern',
	'fcmod-' . $mode,
	'gap-' . $gap,
	$card_shadow ? 'has-shadow' : '',
	$card_hover  ? 'has-hover'  : '',
	$custom_cls,
	$moduleclass_sfx,
]));

// ── Date format ───────────────────────────────────────────────────────────────
$date_format = \Joomla\CMS\Component\ComponentHelper::getParams('com_flexicontent')
	->get('date_format', 'DATE_FORMAT_LC3');
?>

<div id="<?php echo $uid; ?>"
     class="<?php echo $wrapper_classes; ?>"
     style="<?php echo $inline_css; ?>">

	<div class="fcmod-grid">

	<?php foreach ($all_items as $item): ?>

		<?php
		// ── Title ────────────────────────────────────────────────────────
		$title = $item->fulltitle ?? $item->title ?? '';
		if ($title_chars > 0 && StringHelper::strlen($title) > $title_chars) {
			$title = StringHelper::substr($title, 0, $title_chars) . '…';
		}
		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
		$link  = $item->link ?? '#';

		// ── Intro text ────────────────────────────────────────────────────
		$text_raw = strip_tags($item->text ?? '');
		if ($text_chars > 0 && StringHelper::strlen($text_raw) > $text_chars) {
			$text_raw = StringHelper::substr($text_raw, 0, $text_chars) . '…';
		}

		// ── Image ─────────────────────────────────────────────────────────
		$has_image  = $show_image && !empty($item->image);
		$img_src    = $item->image ?? '';
		$img_src_w  = ($img_w > 0) ? $img_w : ($item->image_w ?? 400);
		$img_src_h  = ($img_h > 0) ? $img_h : ($item->image_h ?? 225);

		// ── Date ──────────────────────────────────────────────────────────
		$date_val = '';
		if ($show_date) {
			$raw_date = match ($show_date) {
				'modified'   => $item->modified   ?? $item->created   ?? '',
				'publish_up' => $item->publish_up ?? $item->created   ?? '',
				default      => $item->created    ?? '',
			};
			if ($raw_date && $raw_date !== '0000-00-00 00:00:00') {
				$date_val = HTMLHelper::_('date', $raw_date, \Joomla\CMS\Language\Text::_($date_format));
			}
		}

		// ── Category ──────────────────────────────────────────────────────
		$cat_name = $item->category_title ?? '';
		$cat_link = $item->category_link  ?? '';
		?>

		<article class="fcmod-card-wrap">

			<?php if ($has_image && $mode !== 'compact'): ?>
			<div class="fcmod-card-img"<?php
				if ($img_ratio > 0) echo ' style="padding-top:' . (float)$img_ratio . '%"';
			?>>
				<a href="<?php echo $link; ?>" tabindex="-1" aria-hidden="true">
					<img src="<?php echo htmlspecialchars($img_src); ?>"
					     width="<?php echo $img_src_w; ?>"
					     height="<?php echo $img_src_h; ?>"
					     alt="<?php echo $title; ?>"
					     loading="lazy" />
				</a>
			</div>
			<?php endif; ?>

			<div class="fcmod-card-body">

				<?php if ($show_category || $date_val): ?>
				<div class="fcmod-meta">
					<?php if ($show_category && $cat_name): ?>
					<?php if ($cat_link): ?>
					<a class="fcmod-category-badge" href="<?php echo htmlspecialchars($cat_link); ?>">
						<?php echo htmlspecialchars($cat_name); ?>
					</a>
					<?php else: ?>
					<span class="fcmod-category-badge"><?php echo htmlspecialchars($cat_name); ?></span>
					<?php endif; ?>
					<?php endif; ?>

					<?php if ($date_val): ?>
					<time class="fcmod-date" datetime="<?php echo htmlspecialchars($raw_date ?? ''); ?>">
						<?php echo $date_val; ?>
					</time>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<h3 class="fcmod-title">
					<a href="<?php echo $link; ?>"><?php echo $title; ?></a>
				</h3>

				<?php if ($show_text && $text_raw): ?>
				<p class="fcmod-text"><?php echo htmlspecialchars($text_raw, ENT_QUOTES, 'UTF-8'); ?></p>
				<?php endif; ?>

				<?php if ($show_readmore): ?>
				<a class="fcmod-readmore" href="<?php echo $link; ?>">
					<?php echo $readmore_lbl; ?>
				</a>
				<?php endif; ?>

			</div><!-- .fcmod-card-body -->

		</article><!-- .fcmod-card-wrap -->

	<?php endforeach; ?>

	</div><!-- .fcmod-grid -->

</div><!-- .fcmod-modern -->
