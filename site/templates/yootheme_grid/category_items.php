<?php
/**
 * FLEXIcontent — YOOtheme Grid Template
 * Compatible with YOOtheme Pro (UIkit 3)
 * Fallback: CSS Grid for non-YOOtheme sites
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.popover', '.hasTooltip', ['trigger' => 'click hover']);

$tmpl = $this->tmpl;
$user = Factory::getUser();

// ── Basic params ──────────────────────────────────────────────
$readon_type  = (int) $this->params->get('readon_type', 0);
$readon_image = $this->params->get('readon_image', '');
$readon_class = $this->params->get('readon_class', 'uk-button uk-button-default uk-button-small');
$use_lazy_loading = (int) $this->params->get('use_lazy_loading', 1);
$lazy_loading = $use_lazy_loading ? ' loading="lazy" decoding="async" ' : '';
if ($readon_type && $readon_image && file_exists(\Joomla\Filesystem\Path::clean(JPATH_SITE . DS . $readon_image))) {
	$readon_image = Uri::root(true) . '/' . $readon_image;
}
$readon_type = !$readon_image ? 0 : $readon_type;

// ── Featured params ───────────────────────────────────────────
$leadnum       = (int) $this->params->get('lead_num', 1);
$count         = count($this->items);
$leadnum       = ($leadnum >= $count) ? $count : $leadnum;
if ($this->limitstart != 0) $leadnum = 0;

$feat_card_style     = $this->params->get('feat_card_style', 'hero');
$feat_img_position   = $this->params->get('feat_img_position', 'left');
$feat_img_width      = (int)$this->params->get('feat_img_width', 50);
$feat_content_valign = $this->params->get('feat_content_valign', 'top');
$feat_card_minheight = (int)$this->params->get('feat_card_minheight', 280);
$feat_animation      = $this->params->get('feat_animation', 'fade-up');
$feat_uk_card_style  = $this->params->get('feat_uk_card_style', 'uk-card-default');
$feat_uk_card_hover  = (int)$this->params->get('feat_uk_card_hover', 1);

// ── Standard params ───────────────────────────────────────────
$std_card_style      = $this->params->get('std_card_style', 'classic');
$std_img_width       = (int)$this->params->get('std_img_width', 33);
$std_card_minheight  = (int)$this->params->get('std_card_minheight', 200);
$std_animation       = $this->params->get('std_animation', 'fade-up');
$std_uk_card_style   = $this->params->get('std_uk_card_style', 'uk-card-default');
$std_uk_card_hover   = (int)$this->params->get('std_uk_card_hover', 1);
$uk_img_ratio        = $this->params->get('uk_img_ratio', '16-9');

// UIkit grid columns
$uk_cols_d = (int)$this->params->get('uk_grid_cols_desktop', 3);
$uk_cols_t = (int)$this->params->get('uk_grid_cols_tablet', 2);
$uk_cols_m = (int)$this->params->get('uk_grid_cols_mobile', 1);
$uk_gap    = $this->params->get('uk_grid_gap', '');

// Build UIkit child-width classes
$uk_child = 'uk-child-width-1-' . $uk_cols_m;
if ($uk_cols_t > 1) $uk_child .= '@s';
// tablet = @s (>=640), desktop = @m (>=960)
if ($uk_cols_t != $uk_cols_m) $uk_child .= ' uk-child-width-1-' . $uk_cols_t . '@s';
if ($uk_cols_d != $uk_cols_t) $uk_child .= ' uk-child-width-1-' . $uk_cols_d . '@m';
$uk_grid_class = trim('uk-grid ' . $uk_gap . ' ' . $uk_child);

// ── Display params ────────────────────────────────────────────
$display_date   = $this->params->get('display_date');
$show_title     = (int)$this->params->get('show_title', 1);
$link_titles    = (int)$this->params->get('link_titles', 1);
$show_readmore  = $this->params->get('show_readmore', 1);
$show_editbtn   = (int)$this->params->get('show_editbutton', 1);
$lead_image     = (int)$this->params->get('lead_image', 1);
$lead_image_size= $this->params->get('lead_image_size', 'l');
$intro_image    = (int)$this->params->get('intro_image', 1);
$intro_image_size = $this->params->get('intro_image_size', 'l');
$lead_cut_text  = (int)$this->params->get('lead_cut_text', 300);
$intro_cut_text = (int)$this->params->get('intro_cut_text', 180);

// ── Image sizes ───────────────────────────────────────────────
$img_size_map   = ['l' => 'large', 'm' => 'medium', 's' => 'small', 'o' => 'original'];
$lead_img_field = 'image_' . ($img_size_map[$lead_image_size] ?? 'large');
$intro_img_field= 'image_' . ($img_size_map[$intro_image_size] ?? 'large');

// ── Render fields ─────────────────────────────────────────────
FlexicontentFields::getFieldDisplay($this->items, 'text', null, 'display');

// ── $_tmpl_ init (PHP 8 safe) ─────────────────────────────────
$_tmpl_ = '';

// ── UIkit image ratio class ───────────────────────────────────
$uk_ratio_class = $uk_img_ratio ? 'uk-ratio-' . $uk_img_ratio : '';

// ── Featured UIkit card class ─────────────────────────────────
$feat_uk_cls = trim($feat_uk_card_style . ($feat_uk_card_hover ? ' uk-card-hover' : ''));
$std_uk_cls  = trim($std_uk_card_style  . ($std_uk_card_hover  ? ' uk-card-hover' : ''));

?>
<div id="fc-yootheme-grid" class="yootheme-grid-template">

<?php /* ════════════════════════════════════════════════════════
   FEATURED ITEMS
   ════════════════════════════════════════════════════════ */ ?>

<?php if ($leadnum) : ?>
<div class="fc-featured-section uk-margin-medium-bottom
     fc-feat-style-<?php echo $feat_card_style; ?>
     fc-feat-img-<?php echo $feat_img_position; ?>
     fc-feat-valign-<?php echo $feat_content_valign; ?>"
     style="--fc-feat-img-w:<?php echo $feat_img_width; ?>%;
            --fc-feat-minheight:<?php echo $feat_card_minheight; ?>px;"
     data-feat-anim="<?php echo htmlspecialchars($feat_animation); ?>">

	<?php for ($i = 0; $i < $leadnum; $i++) : ?>
	<?php
		$item = $this->items[$i];
		$link_url = \Joomla\CMS\Router\Route::_(flexicontent_html::getItemRoute($item->slug ?? '', $item->categoryslug ?? '', 0, $item));

		// Edit/State buttons
		$editbutton  = $show_editbtn ? flexicontent_html::editbutton($item, $this->params)  : '';
		$statebutton = $show_editbtn ? flexicontent_html::statebutton($item, $this->params) : '';

		// Image
		$img_html = '';
		if ($lead_image) {
			if (!empty($item->image_rendered)) {
				$img_html = $item->image_rendered;
			} elseif (!empty($item->image)) {
				$img_html = '<img src="' . $item->image . '"'
					. ' alt="' . htmlspecialchars(flexicontent_html::striptagsandcut($item->title ?? '', 60)) . '"'
					. $lazy_loading . ' />';
			}
		}
		$has_image = (bool)$img_html;

		// Excerpt
		$text_display = '';
		if (!empty($item->positions['text'])) {
			foreach ($item->positions['text'] as $field) {
				$text_display .= $field->display ?? '';
			}
		}
	?>

	<div class="fc-feat-wrapper uk-margin-small-bottom"
	     data-index="<?php echo $i; ?>"
	     data-total="<?php echo $leadnum; ?>">
		<div class="fc-feat-innerbox uk-card <?php echo $feat_uk_cls; ?>"
		     style="min-height:var(--fc-feat-minheight,<?php echo $feat_card_minheight; ?>px)">

			<!-- Edit toolbar -->
			<?php if ($editbutton || $statebutton) : ?>
			<div class="fc-edit-toolbar tool">
				<?php if ($editbutton)  echo '<div class="fc_edit_link">' . $editbutton . '</div>'; ?>
				<?php if ($statebutton) echo '<div class="fc_state_toggle_link">' . $statebutton . '</div>'; ?>
			</div>
			<?php endif; ?>

			<!-- Badge -->
			<span class="fc-feat-badge">&#9733; Featured</span>

			<!-- Image -->
			<?php if ($img_html) : ?>
			<figure class="fc-feat-image image_featured uk-cover-container">
				<?php if ($link_url) : ?>
					<a href="<?php echo $link_url; ?>"><?php echo $img_html; ?></a>
				<?php else : ?>
					<?php echo $img_html; ?>
				<?php endif; ?>
			</figure>
			<?php endif; ?>

			<!-- Content -->
			<div class="fc-feat-content <?php echo $feat_uk_card_style === 'uk-card-default' ? 'uk-card-body' : ''; ?>">

				<?php if ($show_title && !empty($item->title)) : ?>
				<h2 class="fc-feat-title uk-card-title">
					<?php if ($link_titles && $link_url) : ?>
						<a href="<?php echo $link_url; ?>" class="uk-link-reset"><?php echo $item->title; ?></a>
					<?php else : ?>
						<?php echo $item->title; ?>
					<?php endif; ?>
				</h2>
				<?php endif; ?>

				<?php if ($text_display) : ?>
				<div class="fc-feat-text uk-text-small uk-margin-small-top">
					<?php echo $text_display; ?>
				</div>
				<?php endif; ?>

				<?php if ($show_readmore) : ?>
				<div class="fc-feat-footer uk-margin-top">
					<a href="<?php echo $link_url; ?>" class="<?php echo $readon_class; ?>">
						<?php echo Text::sprintf('FLEXI_READ_MORE', $item->title ?? ''); ?>
					</a>
				</div>
				<?php endif; ?>

			</div><!-- /content -->
		</div><!-- /innerbox -->
	</div><!-- /wrapper -->

	<?php endfor; ?>

</div><!-- /featured-section -->
<?php endif; ?>

<?php /* ════════════════════════════════════════════════════════
   STANDARD ITEMS
   ════════════════════════════════════════════════════════ */ ?>

<?php if (count($this->items) > $leadnum) : ?>
<div class="fc-standard-section
     fc-std-style-<?php echo $std_card_style; ?>"
     style="--fc-std-img-w:<?php echo $std_img_width; ?>%;
            --fc-std-minheight:<?php echo $std_card_minheight; ?>px;"
     data-std-anim="<?php echo htmlspecialchars($std_animation); ?>">

	<?php
	// UIkit needs uk-grid on a ul, children are li
	// For overlay/horizontal/magazine we use a different wrapper
	$use_uk_list = in_array($std_card_style, ['classic', 'overlay', 'minimal']);
	if ($use_uk_list) : ?>
	<div class="<?php echo $uk_grid_class; ?>" uk-grid>
	<?php else : ?>
	<div class="fc-std-list">
	<?php endif; ?>

	<?php for ($i = $leadnum; $i < count($this->items); $i++) : ?>
	<?php
		$item = $this->items[$i];
		$link_url = \Joomla\CMS\Router\Route::_(flexicontent_html::getItemRoute($item->slug ?? '', $item->categoryslug ?? '', 0, $item));

		$editbutton  = $show_editbtn ? flexicontent_html::editbutton($item, $this->params)  : '';
		$statebutton = $show_editbtn ? flexicontent_html::statebutton($item, $this->params) : '';

		// Image
		$img_html = '';
		if ($intro_image) {
			if (!empty($item->image_rendered)) {
				$img_html = $item->image_rendered;
			} elseif (!empty($item->image)) {
				$img_html = '<img src="' . $item->image . '"'
					. ' alt="' . htmlspecialchars(flexicontent_html::striptagsandcut($item->title ?? '', 60)) . '"'
					. $lazy_loading . ' />';
			}
		}

		// Excerpt
		$text_display = '';
		if (!empty($item->positions['text'])) {
			foreach ($item->positions['text'] as $field) {
				$text_display .= $field->display ?? '';
			}
		}

		// Readmore
		$readmore_text = !empty($item->params) && $item->params->get('readmore')
			? $item->params->get('readmore')
			: Text::sprintf('FLEXI_READ_MORE', $item->title ?? '');
	?>

	<div class="fc-std-item <?php echo ($use_uk_list ? '' : 'uk-margin-bottom'); ?>">
		<div class="fc-std-innerbox uk-card <?php echo $std_uk_cls; ?> uk-position-relative">

			<!-- Edit toolbar -->
			<?php if ($editbutton || $statebutton) : ?>
			<div class="fc-edit-toolbar tool">
				<?php if ($editbutton)  echo '<div class="fc_edit_link">' . $editbutton . '</div>'; ?>
				<?php if ($statebutton) echo '<div class="fc_state_toggle_link">' . $statebutton . '</div>'; ?>
			</div>
			<?php endif; ?>

			<!-- Image -->
			<?php if ($img_html && $std_card_style !== 'minimal') : ?>
			<figure class="fc-std-image image_standard <?php
				if ($std_card_style === 'overlay') echo 'uk-cover-container uk-position-cover';
				elseif ($uk_img_ratio && $std_card_style === 'classic') echo 'uk-' . $uk_img_ratio;
			?>">
				<?php if ($link_url) : ?><a href="<?php echo $link_url; ?>"><?php endif; ?>
				<?php echo $img_html; ?>
				<?php if ($link_url) : ?></a><?php endif; ?>
			</figure>
			<?php endif; ?>

			<!-- Content -->
			<div class="fc-std-content <?php echo $std_uk_card_style === 'uk-card-default' || $std_uk_card_style === 'uk-card-secondary' || $std_uk_card_style === 'uk-card-primary' ? 'uk-card-body' : ''; ?>
				<?php if ($std_card_style === 'minimal') echo 'fc-std-minimal-content'; ?>">

				<?php if ($show_title && !empty($item->title)) : ?>
				<h3 class="fc-std-title uk-card-title <?php echo $std_card_style === 'overlay' ? 'uk-text-contrast' : ''; ?>">
					<?php if ($link_titles && $link_url) : ?>
						<a href="<?php echo $link_url; ?>" class="uk-link-reset"><?php echo $item->title; ?></a>
					<?php else : ?>
						<?php echo $item->title; ?>
					<?php endif; ?>
				</h3>
				<?php endif; ?>

				<?php if ($text_display) : ?>
				<div class="fc-std-text uk-text-small uk-margin-small-top <?php echo $std_card_style === 'overlay' ? 'uk-text-contrast' : ''; ?>">
					<?php echo $text_display; ?>
				</div>
				<?php endif; ?>

				<?php if ($show_readmore) : ?>
				<div class="fc-std-readmore uk-margin-small-top">
					<a href="<?php echo $link_url; ?>" class="<?php echo $readon_class; ?>">
						<span uk-icon="icon: chevron-right; ratio: 0.8"></span>
						<?php echo $readmore_text; ?>
					</a>
				</div>
				<?php endif; ?>

			</div><!-- /content -->
		</div><!-- /innerbox -->
	</div><!-- /item -->

	<?php endfor; ?>

	</div><!-- /uk-grid or fc-std-list -->
</div><!-- /standard-section -->
<?php endif; ?>

</div><!-- /yootheme-grid-template -->
