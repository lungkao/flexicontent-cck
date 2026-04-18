<?php
/**
 * FLEXIcontent — YOOtheme Grid Template: Item View (Standalone, Modern UIkit 3)
 *
 * Reads $item->positions directly — no dependency on modular.php.
 * Hero image → card body (title + content fields) → bottom grid → UIkit tab.
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;

$doc    = Factory::getDocument();
$app    = Factory::getApplication();
$item   = $this->item;
$params = $this->params;
$menu   = $app->getMenu()->getActive();

// ── Inject scoped CSS ─────────────────────────────────────────────────────
$doc->getWebAssetManager()->addInlineStyle('
/* ══ fc-yootheme-item: Modern standalone item view ══════════════════════ */

/* Container */
#fc-yootheme-item { padding: 2rem 0 3rem; }

/* Card */
#fc-yootheme-item .fc-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 24px rgba(0,0,0,.09);
  overflow: hidden;
}

/* ── Hero image ── */
#fc-yootheme-item .fc-hero { position: relative; }
#fc-yootheme-item .fc-hero img,
#fc-yootheme-item .fc-hero a > img {
  display: block; width: 100%; height: auto;
  aspect-ratio: 16/6; object-fit: cover;
}
/* fancybox / gallery wrapper */
#fc-yootheme-item .fc-hero .fc-gallery-wrap { line-height: 0; }
#fc-yootheme-item .fc-hero .fc-gallery-wrap a { display: block; }

/* ── Card body ── */
#fc-yootheme-item .fc-body { padding: 2.5rem; }

/* Toolbar (edit / state buttons) */
#fc-yootheme-item .fc-toolbar {
  float: right; display: flex; gap: .4rem;
  margin: 0 0 .75rem .75rem;
}
#fc-yootheme-item .fc-toolbar a { font-size: .8rem; }

/* Print / action buttons bar */
#fc-yootheme-item .fc-action-bar {
  display: flex; flex-wrap: wrap; gap: .5rem;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f0f0f0;
}

/* Title */
#fc-yootheme-item .fc-title {
  margin: 0 0 1.25rem;
  font-size: clamp(1.5rem, 3vw, 2.25rem);
  font-weight: 700; line-height: 1.2; color: #111827;
}

/* ── Description / content fields ── */
#fc-yootheme-item .fc-content { color: #374151; line-height: 1.8; }
#fc-yootheme-item .fc-content p:last-child { margin-bottom: 0; }

/* Individual custom field blocks in description */
#fc-yootheme-item .fc-field-wrap { margin-bottom: 1.25rem; }
#fc-yootheme-item .fc-field-label {
  display: block; margin-bottom: .25rem;
  font-size: .7rem; font-weight: 700; letter-spacing: .06em;
  text-transform: uppercase; color: #9ca3af;
}

/* ── Card footer (bottom fields grid) ── */
#fc-yootheme-item .fc-footer {
  background: #f8fafc;
  border-top: 1px solid #e5e7eb;
  padding: 1.75rem 2.5rem;
}
#fc-yootheme-item .fc-footer .fc-section-heading {
  font-size: .75rem; font-weight: 700; letter-spacing: .06em;
  text-transform: uppercase; color: #6b7280;
  margin: 0 0 1rem;
}
#fc-yootheme-item .fc-footer-field { margin-bottom: .5rem; }

/* ── Tabs ── */
#fc-yootheme-item .fc-tabs-wrap { margin-top: 1.5rem; }
#fc-yootheme-item .fc-tabs-wrap .uk-tab::before { border-color: #e5e7eb; }

/* ── Events wrappers ── */
#fc-yootheme-item .fc-event-block { margin-bottom: 1rem; }

/* ── Image gallery overrides ── (fancybox containers) */
#fc-yootheme-item .fc-body .image_featured,
#fc-yootheme-item .fc-body .flexi.image {
  margin: 0; text-align: center;
}
#fc-yootheme-item .fc-body .image_featured img,
#fc-yootheme-item .fc-body .flexi.image img {
  max-width: 100%; height: auto;
}

/* ── Responsive ── */
@media (max-width: 639px) {
  #fc-yootheme-item .fc-body,
  #fc-yootheme-item .fc-footer { padding: 1.25rem; }
  #fc-yootheme-item .fc-title { font-size: 1.5rem; }
}
', 'fc-yootheme-item');

// ── Variables ─────────────────────────────────────────────────────────────
$show_title  = (int)$params->get('show_title', 1);
$link_titles = (int)$params->get('link_titles', 1);
$show_print  = (int)$params->get('show_print_icon', 0);
$show_email  = (int)$params->get('show_email_icon', 0);

// Action buttons
$editbutton     = flexicontent_html::editbutton($item, $params);
$statebutton    = flexicontent_html::statebutton($item, $params);
$deletebutton   = flexicontent_html::deletebutton($item, $params);
$approvalbutton = flexicontent_html::approvalbutton($item, $params);
$printbutton    = flexicontent_html::printbutton($this->print_link, $params);
$mailbutton     = flexicontent_html::mailbutton(FLEXI_ITEMVIEW, $params, $item->categoryslug, $item->slug, 0, $item);
$pdfbutton      = flexicontent_html::pdfbutton($item, $params);

// Page-heading support
$page_heading_shown =
	$params->get('show_page_heading', 1) &&
	$params->get('page_heading') != $item->title &&
	$show_title;
$title_tag = $page_heading_shown ? 'h2' : 'h1';

// Microdata
$microdata_type = $params->get('microdata_itemtype', 'Article');

// Page suffix class
$page_class = $this->pageclass_sfx ? ' page' . $this->pageclass_sfx : '';
$article_class = 'flexicontent fcitems fcitem' . $item->id
	. ' fctype' . $item->type_id
	. ' fcmaincat' . $item->catid
	. ($menu ? ' menuitem' . $menu->id : '')
	. $page_class;

// ── Positions ─────────────────────────────────────────────────────────────
$pos = (array)($item->positions ?? []);

// Hero image: prefer 'image' position, fall back to 'subtitle3'
$hero_pos  = $pos['image'] ?? $pos['subtitle3'] ?? null;
$descr_pos = $pos['description'] ?? null;
$bottom_pos = $pos['bottom'] ?? null;

// Bottom tabs (bottom_tab1 … bottom_tab12)
$tab_positions = [];
for ($t = 1; $t <= 12; $t++) {
	$tk = 'bottom_tab' . $t;
	if (!empty($pos[$tk])) {
		$tab_positions[$tk] = $pos[$tk];
	}
}

// TOC: prepend to text field display if present
if (!empty($item->toc) && isset($item->fields['text'])) {
	$item->fields['text']->display = $item->toc . ($item->fields['text']->display ?? '');
}

// ── Render hero image HTML ─────────────────────────────────────────────────
$hero_html = '';
if ($hero_pos) {
	foreach ((array)$hero_pos as $f) {
		if (!empty($f->display)) { $hero_html = $f->display; break; }
	}
}

// ── Print mode ─────────────────────────────────────────────────────────────
$is_print = (bool)$app->getInput()->getInt('print', 0);
?>

<article id="fc-yootheme-item"
         class="<?php echo $article_class; ?>"
         itemscope itemtype="http://schema.org/<?php echo $microdata_type; ?>">

<?php if ($is_print) : ?>
	<?php if ($params->get('print_behaviour', 'auto') === 'auto') : ?>
		<script>jQuery(document).ready(function(){ window.print(); });</script>
	<?php elseif ($params->get('print_behaviour') === 'button') : ?>
		<input type="button" value="<?php echo Text::_('Print'); ?>"
		       class="btn btn-info"
		       onclick="this.style.display='none';window.print();return false;">
	<?php endif; ?>
<?php endif; ?>

<div class="uk-container uk-container-small"
     uk-scrollspy="target:.fc-card;cls:uk-animation-fade;delay:80">

	<?php if ($page_heading_shown) : ?>
	<h1 class="uk-heading-small uk-margin-bottom">
		<?php echo $params->get('page_heading'); ?>
	</h1>
	<?php endif; ?>

	<!-- ══ Main card ══════════════════════════════════════════════════════ -->
	<div class="fc-card">

		<!-- Hero image -->
		<?php if ($hero_html) : ?>
		<div class="fc-hero">
			<?php echo $hero_html; ?>
		</div>
		<?php endif; ?>

		<!-- Card body -->
		<div class="fc-body">

			<!-- Toolbar (edit / state / delete) -->
			<?php if ($editbutton || $statebutton || $deletebutton || $approvalbutton) : ?>
			<div class="fc-toolbar">
				<?php echo $editbutton; ?>
				<?php echo $statebutton; ?>
				<?php echo $deletebutton; ?>
				<?php echo $approvalbutton; ?>
			</div>
			<?php endif; ?>

			<!-- Action bar (print / mail / pdf) -->
			<?php if ($printbutton || $mailbutton || $pdfbutton) : ?>
			<div class="fc-action-bar">
				<?php echo $pdfbutton; ?>
				<?php echo $mailbutton; ?>
				<?php echo $printbutton; ?>
			</div>
			<?php endif; ?>

			<!-- Before-content plugin event -->
			<?php if (!empty($item->event->beforeDisplayContent)) : ?>
			<div class="fc-event-block"><?php echo $item->event->beforeDisplayContent; ?></div>
			<?php endif; ?>

			<!-- Title -->
			<?php if ($show_title && !empty($item->title)) : ?>
			<<?php echo $title_tag; ?> class="fc-title" itemprop="name">
				<?php
				$title_text = (StringHelper::strlen($item->title) > (int)$params->get('title_cut_text', 200))
					? StringHelper::substr($item->title, 0, (int)$params->get('title_cut_text', 200)) . ' …'
					: $item->title;
				echo $title_text;
				?>
			</<?php echo $title_tag; ?>>
			<?php endif; ?>

			<!-- After-title plugin event -->
			<?php if (!empty($item->event->afterDisplayTitle)) : ?>
			<div class="fc-event-block"><?php echo $item->event->afterDisplayTitle; ?></div>
			<?php endif; ?>

			<!-- ── Description / content fields ─────────────────────── -->
			<?php if ($descr_pos) : ?>
			<div class="fc-content">
				<?php foreach ((array)$descr_pos as $field) : ?>
					<?php if (empty($field->display)) continue; ?>
					<div class="fc-field-wrap fc-field-<?php echo htmlspecialchars($field->name); ?>">
						<?php if ($field->label) : ?>
						<span class="fc-field-label"><?php echo $field->label; ?></span>
						<?php endif; ?>
						<?php echo $field->display; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<!-- After-content plugin event -->
			<?php if (!empty($item->event->afterDisplayContent)) : ?>
			<div class="fc-event-block uk-margin-top"><?php echo $item->event->afterDisplayContent; ?></div>
			<?php endif; ?>

		</div><!-- /fc-body -->

		<!-- ── Card footer: bottom fields ───────────────────────────────── -->
		<?php if ($bottom_pos) : ?>
		<div class="fc-footer">
			<p class="fc-section-heading"><?php echo Text::_('FLEXI_ADDITIONAL_INFORMATION'); ?></p>
			<div class="uk-child-width-1-2@s uk-grid-small" uk-grid>
				<?php foreach ((array)$bottom_pos as $field) : ?>
					<?php if (empty($field->display)) continue; ?>
					<div class="fc-footer-field">
						<?php if ($field->label) : ?>
						<span class="fc-field-label"><?php echo $field->label; ?></span>
						<?php endif; ?>
						<div><?php echo $field->display; ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

	</div><!-- /fc-card -->

	<!-- ── Tabbed content (bottom_tab1 … bottom_tab12) ──────────────────── -->
	<?php if ($tab_positions) : ?>
	<div class="fc-tabs-wrap" uk-scrollspy="cls:uk-animation-slide-bottom-small;delay:150">
		<ul class="uk-tab" uk-tab>
			<?php foreach ($tab_positions as $tk => $tfields) :
				$tc = (int)substr($tk, 10);
				$label = Text::_($params->get('bottom_tab' . $tc . '_label', 'Tab ' . $tc));
			?>
			<li><a href="#"><?php echo $label; ?></a></li>
			<?php endforeach; ?>
		</ul>
		<ul class="uk-switcher uk-margin-small-top">
			<?php foreach ($tab_positions as $tk => $tfields) : ?>
			<li>
				<?php foreach ((array)$tfields as $field) : ?>
					<?php if (empty($field->display)) continue; ?>
					<div class="fc-field-wrap fc-field-<?php echo htmlspecialchars($field->name); ?>">
						<?php if ($field->label) : ?>
						<span class="fc-field-label"><?php echo $field->label; ?></span>
						<?php endif; ?>
						<?php echo $field->display; ?>
					</div>
				<?php endforeach; ?>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<!-- ── Comments ─────────────────────────────────────────────────────── -->
	<?php if ($params->get('comments') && !$is_print) : ?>
	<section class="fc-comments uk-margin-medium-top">
		<?php
		if ($params->get('comments') == 1 &&
		    file_exists(JPATH_SITE . DS . 'components' . DS . 'com_jcomments' . DS . 'jcomments.php')) {
			require_once JPATH_SITE . DS . 'components' . DS . 'com_jcomments' . DS . 'jcomments.php';
			echo JComments::showComments($item->id, 'com_flexicontent', $this->escape($item->title));
		}
		?>
	</section>
	<?php endif; ?>

</div><!-- /uk-container -->
</article>
