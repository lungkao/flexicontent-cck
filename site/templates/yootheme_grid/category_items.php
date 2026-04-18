<?php
/**
 * FLEXIcontent — YOOtheme Grid Template: Category Items View (Modern UIkit 3)
 * Featured items: full-width hero card with image + text
 * Standard items: responsive grid of image cards
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.popover', '.hasTooltip', ['trigger' => 'click hover']);

// ── Inject CSS ─────────────────────────────────────────────────────────────
$doc = Factory::getDocument();
$doc->getWebAssetManager()->addInlineStyle('
/* ══ fc-yootheme-grid: Category Items ═══════════════════════════════════ */

/* ── Featured card ── */
.fc-feat-card {
  position: relative; overflow: hidden;
  border-radius: 14px;
  box-shadow: 0 4px 28px rgba(0,0,0,.10);
  background: #fff;
  margin-bottom: 2rem;
}
/* Image: full-width, 16:6 ratio */
.fc-feat-card .fc-feat-img {
  line-height: 0; overflow: hidden;
  border-radius: 14px 14px 0 0;
}
.fc-feat-card .fc-feat-img img,
.fc-feat-card .fc-feat-img a > img {
  display: block; width: 100%; height: auto;
  aspect-ratio: 16/6; object-fit: cover;
  transition: transform .4s ease;
}
.fc-feat-card:hover .fc-feat-img img { transform: scale(1.03); }
/* Badge */
.fc-feat-card .fc-badge {
  position: absolute; top: 1rem; left: 1rem;
  background: #1d4ed8; color: #fff;
  font-size: .7rem; font-weight: 700; letter-spacing: .05em;
  text-transform: uppercase;
  padding: .25rem .6rem; border-radius: 20px;
}
/* Body */
.fc-feat-card .fc-feat-body { padding: 1.75rem 2rem 2rem; }
.fc-feat-card .fc-feat-title {
  font-size: clamp(1.25rem, 2.5vw, 1.75rem);
  font-weight: 700; line-height: 1.25; color: #111827;
  margin: 0 0 .75rem;
}
.fc-feat-card .fc-feat-title a { color: inherit; text-decoration: none; }
.fc-feat-card .fc-feat-title a:hover { color: #1d4ed8; }
.fc-feat-card .fc-feat-text {
  color: #4b5563; line-height: 1.7;
  margin-bottom: 1.25rem;
  display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;
  overflow: hidden;
}
.fc-feat-card .fc-feat-footer { display: flex; align-items: center; gap: .75rem; }

/* ── Standard grid cards ── */
.fc-std-grid { margin-top: 1rem; }
.fc-std-card {
  background: #fff; border-radius: 12px;
  box-shadow: 0 2px 14px rgba(0,0,0,.07);
  overflow: hidden; display: flex; flex-direction: column;
  transition: box-shadow .25s, transform .25s;
  height: 100%;
}
.fc-std-card:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,.14);
  transform: translateY(-3px);
}
/* Image */
.fc-std-card .fc-std-img { overflow: hidden; line-height: 0; flex-shrink: 0; }
.fc-std-card .fc-std-img img,
.fc-std-card .fc-std-img a > img {
  display: block; width: 100%; height: auto;
  aspect-ratio: 16/9; object-fit: cover;
  transition: transform .35s ease;
}
.fc-std-card:hover .fc-std-img img { transform: scale(1.05); }
/* No-image placeholder */
.fc-std-card .fc-std-noimg {
  aspect-ratio: 16/9; background: linear-gradient(135deg,#e0e7ff,#f0f4ff);
  display: flex; align-items: center; justify-content: center;
  color: #818cf8; font-size: 2rem;
}
/* Body */
.fc-std-card .fc-std-body { padding: 1.1rem 1.25rem 1.25rem; flex: 1; display: flex; flex-direction: column; }
.fc-std-card .fc-std-title {
  font-size: 1.05rem; font-weight: 600; line-height: 1.35;
  color: #111827; margin: 0 0 .5rem;
}
.fc-std-card .fc-std-title a { color: inherit; text-decoration: none; }
.fc-std-card .fc-std-title a:hover { color: #1d4ed8; }
.fc-std-card .fc-std-text {
  font-size: .875rem; color: #6b7280; line-height: 1.6; flex: 1;
  display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
  overflow: hidden; margin-bottom: .75rem;
}
.fc-std-card .fc-std-footer { margin-top: auto; }

/* Readmore button */
.fc-btn-readmore {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .8rem; font-weight: 600; letter-spacing: .02em;
  color: #1d4ed8; text-decoration: none;
  padding: .35rem 0; border-bottom: 2px solid transparent;
  transition: border-color .2s, color .2s;
}
.fc-btn-readmore:hover { border-bottom-color: #1d4ed8; color: #1e40af; }
.fc-btn-readmore svg { flex-shrink: 0; }

/* Edit toolbar */
.fc-edit-toolbar { float: right; display: flex; gap: .35rem; margin-left: .5rem; }

/* Wrapper */
.fc-yootheme-grid { padding: .5rem 0 2rem; }

/* Responsive: single column on mobile for grid */
@media (max-width: 639px) {
  .fc-feat-card .fc-feat-body { padding: 1.1rem 1.1rem 1.25rem; }
}
', ['name' => 'fc-yootheme-cat']);

$tmpl = $this->tmpl;
$user = Factory::getUser();

// ── Basic params ──────────────────────────────────────────────────────────
$readon_type  = (int)$this->params->get('readon_type', 0);
$readon_image = $this->params->get('readon_image', '');
$use_lazy     = (int)$this->params->get('use_lazy_loading', 1);
$lazy         = $use_lazy ? ' loading="lazy" decoding="async"' : '';

if ($readon_type && $readon_image && file_exists(\Joomla\Filesystem\Path::clean(JPATH_SITE . DS . $readon_image))) {
	$readon_image = Uri::root(true) . '/' . $readon_image;
}
$readon_type = !$readon_image ? 0 : $readon_type;

// ── Featured / lead params ────────────────────────────────────────────────
$leadnum = (int)$this->params->get('lead_num', 1);
$count   = count($this->items);
$leadnum = min($leadnum, $count);
if ($this->limitstart != 0) $leadnum = 0;

// ── UIkit grid columns ────────────────────────────────────────────────────
$uk_cols_d = (int)$this->params->get('uk_grid_cols_desktop', 3);
$uk_cols_t = (int)$this->params->get('uk_grid_cols_tablet', 2);
$uk_cols_m = (int)$this->params->get('uk_grid_cols_mobile', 1);
$uk_gap    = $this->params->get('uk_grid_gap', 'uk-grid-match');

// Build UIkit child-width classes
$uk_child = 'uk-child-width-1-' . $uk_cols_m;
if ($uk_cols_t > 1) $uk_child .= '@s';
// tablet = @s (>=640), desktop = @m (>=960)
if ($uk_cols_t != $uk_cols_m) $uk_child .= ' uk-child-width-1-' . $uk_cols_t . '@s';
if ($uk_cols_d != $uk_cols_t) $uk_child .= ' uk-child-width-1-' . $uk_cols_d . '@m';

// ── Display params ────────────────────────────────────────────────────────
$show_title    = (int)$this->params->get('show_title', 1);
$link_titles   = (int)$this->params->get('link_titles', 1);
$show_readmore = $this->params->get('show_readmore', 1);
$show_editbtn  = (int)$this->params->get('show_editbutton', 1);
$lead_image    = (int)$this->params->get('lead_image', 1);
$intro_image   = (int)$this->params->get('intro_image', 1);
$lead_cut_text  = (int)$this->params->get('lead_cut_text', 300);
$intro_cut_text = (int)$this->params->get('intro_cut_text', 180);

// ── Render text field for all items ──────────────────────────────────────
FlexicontentFields::getFieldDisplay($this->items, 'text', null, 'display');

// ── Render image field for category cards ────────────────────────────────
// Pass 'category' view so the plugin uses default_method_cat (display_single)
FlexicontentFields::getFieldDisplay($this->items, 'image', null, 'display', 'category');

// ── Helpers (closures — safe to reuse across multiple template includes) ──
$fc_item_image = static function ($item, $want_image, $lazy) {
	if (!$want_image) return '';
	if (!empty($item->fields['image']->display))  return $item->fields['image']->display;
	if (!empty($item->image_rendered))             return $item->image_rendered;
	if (!empty($item->image)) {
		$alt = htmlspecialchars($item->title ?? '', ENT_QUOTES);
		return '<img src="' . htmlspecialchars($item->image) . '" alt="' . $alt . '"' . $lazy . ' />';
	}
	return '';
};

$fc_item_text = static function ($item) {
	foreach (['description', 'text'] as $poskey) {
		if (!empty($item->positions[$poskey])) {
			$out = '';
			foreach ((array)$item->positions[$poskey] as $f) $out .= $f->display ?? '';
			if ($out) return $out;
		}
	}
	return $item->fields['text']->display ?? '';
};

// ── SVG chevron icon ──────────────────────────────────────────────────────
$chevron = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';

?>
<div class="fc-yootheme-grid" id="fc-yootheme-grid">

<?php /* ════════════════════════════════════════════════════════════════
   FEATURED ITEMS
   ════════════════════════════════════════════════════════════════ */ ?>

<?php if ($leadnum > 0) : ?>
<div class="fc-featured-section"
     uk-scrollspy="target:.fc-feat-card;cls:uk-animation-fade;delay:80">

	<?php for ($i = 0; $i < $leadnum; $i++) :
		$item     = $this->items[$i];
		$link_url = \Joomla\CMS\Router\Route::_(
			FlexicontentHelperRoute::getItemRoute($item->slug ?? '', $item->categoryslug ?? '', 0, $item)
		);

		$editbutton  = $show_editbtn ? flexicontent_html::editbutton($item, $this->params) : '';
		$statebutton = $show_editbtn ? flexicontent_html::statebutton($item, $this->params) : '';

		$img_html = $fc_item_image($item, $lead_image, $lazy);
		$text_out = $fc_item_text($item);

		$readmore_text = !empty($item->params) && $item->params->get('readmore')
			? $item->params->get('readmore')
			: Text::sprintf('FLEXI_READ_MORE', '');
	?>

	<div class="fc-feat-card">

		<!-- Edit toolbar -->
		<?php if ($editbutton || $statebutton) : ?>
		<div class="fc-edit-toolbar">
			<?php if ($editbutton)  echo '<span class="fc_edit_link">'  . $editbutton  . '</span>'; ?>
			<?php if ($statebutton) echo '<span class="fc_state_toggle_link">' . $statebutton . '</span>'; ?>
		</div>
		<?php endif; ?>

		<!-- Featured badge -->
		<span class="fc-badge">&#9733; Featured</span>

		<!-- Image -->
		<?php if ($img_html) : ?>
		<div class="fc-feat-img">
			<?php if ($link_url) : ?><a href="<?php echo $link_url; ?>"><?php endif; ?>
			<?php echo $img_html; ?>
			<?php if ($link_url) : ?></a><?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="fc-feat-body">

			<!-- Title -->
			<?php if ($show_title && !empty($item->title)) : ?>
			<h2 class="fc-feat-title">
				<?php if ($link_titles && $link_url) : ?>
				<a href="<?php echo $link_url; ?>"><?php echo $item->title; ?></a>
				<?php else : ?>
				<?php echo $item->title; ?>
				<?php endif; ?>
			</h2>
			<?php endif; ?>

			<!-- Text excerpt -->
			<?php if ($text_out) : ?>
			<div class="fc-feat-text uk-text-small"><?php echo $text_out; ?></div>
			<?php endif; ?>

			<!-- Read more -->
			<?php if ($show_readmore && $link_url) : ?>
			<div class="fc-feat-footer">
				<a href="<?php echo $link_url; ?>" class="fc-btn-readmore">
					<?php echo $readmore_text; ?> <?php echo $chevron; ?>
				</a>
			</div>
			<?php endif; ?>

		</div><!-- /fc-feat-body -->
	</div><!-- /fc-feat-card -->

	<?php endfor; ?>

</div><!-- /fc-featured-section -->
<?php endif; ?>


<?php /* ════════════════════════════════════════════════════════════════
   STANDARD GRID ITEMS
   ════════════════════════════════════════════════════════════════ */ ?>

<?php if ($count > $leadnum) : ?>
<div class="fc-std-grid">
<div class="<?php echo $uk_child . ' ' . $uk_gap; ?>" uk-grid
     uk-scrollspy="target:.fc-std-card;cls:uk-animation-slide-bottom-small;delay:60">

	<?php for ($i = $leadnum; $i < $count; $i++) :
		$item     = $this->items[$i];
		$link_url = \Joomla\CMS\Router\Route::_(
			FlexicontentHelperRoute::getItemRoute($item->slug ?? '', $item->categoryslug ?? '', 0, $item)
		);

		$editbutton  = $show_editbtn ? flexicontent_html::editbutton($item, $this->params) : '';
		$statebutton = $show_editbtn ? flexicontent_html::statebutton($item, $this->params) : '';

		$img_html = $fc_item_image($item, $intro_image, $lazy);
		$text_out = $fc_item_text($item);

		$readmore_text = !empty($item->params) && $item->params->get('readmore')
			? $item->params->get('readmore')
			: Text::sprintf('FLEXI_READ_MORE', '');
	?>

	<div><!-- uk-grid child -->
	<div class="fc-std-card">

		<!-- Edit toolbar -->
		<?php if ($editbutton || $statebutton) : ?>
		<div class="fc-edit-toolbar">
			<?php if ($editbutton)  echo '<span class="fc_edit_link">'  . $editbutton  . '</span>'; ?>
			<?php if ($statebutton) echo '<span class="fc_state_toggle_link">' . $statebutton . '</span>'; ?>
		</div>
		<?php endif; ?>

		<!-- Image or placeholder -->
		<?php if ($img_html) : ?>
		<div class="fc-std-img">
			<?php if ($link_url) : ?><a href="<?php echo $link_url; ?>"><?php endif; ?>
			<?php echo $img_html; ?>
			<?php if ($link_url) : ?></a><?php endif; ?>
		</div>
		<?php else : ?>
		<div class="fc-std-noimg">
			<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
		</div>
		<?php endif; ?>

		<div class="fc-std-body">

			<!-- Title -->
			<?php if ($show_title && !empty($item->title)) : ?>
			<h3 class="fc-std-title">
				<?php if ($link_titles && $link_url) : ?>
				<a href="<?php echo $link_url; ?>"><?php echo $item->title; ?></a>
				<?php else : ?>
				<?php echo $item->title; ?>
				<?php endif; ?>
			</h3>
			<?php endif; ?>

			<!-- Text excerpt -->
			<?php if ($text_out) : ?>
			<div class="fc-std-text"><?php echo strip_tags($text_out); ?></div>
			<?php endif; ?>

			<!-- Read more -->
			<?php if ($show_readmore && $link_url) : ?>
			<div class="fc-std-footer">
				<a href="<?php echo $link_url; ?>" class="fc-btn-readmore">
					<?php echo $readmore_text; ?> <?php echo $chevron; ?>
				</a>
			</div>
			<?php endif; ?>

		</div><!-- /fc-std-body -->
	</div><!-- /fc-std-card -->
	</div><!-- /uk-grid child -->

	<?php endfor; ?>

</div><!-- /uk-grid -->
</div><!-- /fc-std-grid -->
<?php endif; ?>

</div><!-- /fc-yootheme-grid -->
