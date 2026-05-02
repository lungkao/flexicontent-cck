<?php
/**
 * @package         FLEXIcontent
 * @subpackage      Pro Templates — Theme Editor
 *
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var FlexicontentViewProtheme $this */

$document = \Joomla\CMS\Factory::getDocument();
$document->addScript(
	'https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js',
	['version' => 'auto'],
	['defer' => true]
);
$document->addStyleSheet(
	\Joomla\CMS\Uri\Uri::root(true) . '/administrator/components/com_flexicontent/assets/css/protemplate_builder.css',
	['version' => 'auto']
);

$item      = $this->item;
$themeJson = (!empty($item->theme_data)) ? $item->theme_data : '{}';
?>

<form action="<?= Route::_('index.php?option=com_flexicontent&view=prothemes&layout=edit&id=' . (int) ($item->id ?? 0)) ?>"
      method="post" name="adminForm" id="adminForm">

	<input type="hidden" name="jform[id]"         value="<?= (int) ($item->id ?? 0) ?>">
	<input type="hidden" name="jform[theme_data]" id="fcpt_theme_data_input" value="">
	<?= HTMLHelper::_('form.token') ?>

	<!-- Header -->
	<div class="fcpt-details-card mb-3">
		<div class="fcpt-details-intro">
			<span class="fcpt-setup-icon" aria-hidden="true">🎨</span>
			<div>
				<p class="fcpt-kicker mb-1"><?= Text::_('FLEXI_PROTHEME_SETUP') ?></p>
				<h3><?= Text::_('FLEXI_PROTHEME_EDITOR_TITLE') ?></h3>
				<p><?= Text::_('FLEXI_PROTHEME_EDITOR_SUBTITLE') ?></p>
			</div>
		</div>
		<div class="fcpt-detail-field is-title">
			<?= $this->form->renderField('title') ?>
		</div>
		<div class="fcpt-details-side">
			<div class="fcpt-detail-field"><?= $this->form->renderField('state') ?></div>
			<div class="fcpt-detail-field"><?= $this->form->renderField('ordering') ?></div>
		</div>
	</div>

	<!-- Theme Editor (Alpine) -->
	<div id="fcpt-theme-editor"
	     x-data="fcptThemeEditor()"
	     x-init="init()"
	     class="fcpt-theme-editor">

		<div class="fcpt-theme-editor-cols">

			<!-- Color & Typography Controls -->
			<div class="fcpt-theme-controls">
				<h4 class="fcpt-kicker"><?= Text::_('FLEXI_PROTHEME_COLORS') ?></h4>

				<div class="fcpt-theme-row">
					<label>Accent</label>
					<input type="color" x-model="theme.colors.accent">
					<input type="text"  x-model="theme.colors.accent" class="form-control form-control-sm" placeholder="#2563eb">
				</div>
				<div class="fcpt-theme-row">
					<label>Text</label>
					<input type="color" x-model="theme.colors.text">
					<input type="text"  x-model="theme.colors.text"   class="form-control form-control-sm" placeholder="#172033">
				</div>
				<div class="fcpt-theme-row">
					<label>Muted</label>
					<input type="color" x-model="theme.colors.muted">
					<input type="text"  x-model="theme.colors.muted"  class="form-control form-control-sm" placeholder="#6b7280">
				</div>
				<div class="fcpt-theme-row">
					<label>Surface</label>
					<input type="color" x-model="theme.colors.surface">
					<input type="text"  x-model="theme.colors.surface" class="form-control form-control-sm" placeholder="#ffffff">
				</div>
				<div class="fcpt-theme-row">
					<label>Border</label>
					<input type="color" x-model="theme.colors.border">
					<input type="text"  x-model="theme.colors.border"  class="form-control form-control-sm" placeholder="#e5e7eb">
				</div>

				<h4 class="fcpt-kicker mt-4"><?= Text::_('FLEXI_PROTHEME_TYPOGRAPHY') ?></h4>

				<div class="fcpt-theme-row">
					<label>Font family</label>
					<input type="text" x-model="theme.fontFamily" class="form-control form-control-sm" placeholder="Inter, sans-serif">
				</div>
				<div class="fcpt-theme-row">
					<label>Base font size</label>
					<input type="text" x-model="theme.fontSize" class="form-control form-control-sm" placeholder="16px">
				</div>
				<div class="fcpt-theme-row">
					<label>Heading font</label>
					<input type="text" x-model="theme.headingFontFamily" class="form-control form-control-sm" placeholder="Same as body">
				</div>
				<div class="fcpt-theme-row">
					<label>Heading weight</label>
					<select x-model="theme.headingFontWeight" class="form-select form-select-sm">
						<option value="">Default</option>
						<option value="400">400 Normal</option>
						<option value="500">500 Medium</option>
						<option value="600">600 Semi-bold</option>
						<option value="700">700 Bold</option>
						<option value="800">800 Extra-bold</option>
					</select>
				</div>
			</div>

			<!-- Live Preview -->
			<div class="fcpt-theme-preview" :style="previewStyle()">
				<div style="padding:2rem">
					<p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem;opacity:.6">
						<?= Text::_('FLEXI_PROTHEME_PREVIEW') ?>
					</p>
					<h2 :style="'font-family:' + headingFontCss() + ';color:' + (theme.colors.text||'#172033') + ';font-weight:' + (theme.headingFontWeight||'700')">
						Article Title
					</h2>
					<p :style="'font-family:' + bodyFontCss() + ';color:' + (theme.colors.text||'#172033') + ';font-size:' + (theme.fontSize||'16px')">
						This is the intro text of a FLEXIcontent item. Colors, fonts, and spacing are all synced from this theme to the frontend.
					</p>
					<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem">
						<span :style="'background:' + (theme.colors.accent||'#2563eb') + ';color:#fff;padding:.25rem .75rem;border-radius:1rem;font-size:.8rem'">Tag</span>
						<span :style="'background:' + (theme.colors.surface||'#f8f9fa') + ';color:' + (theme.colors.muted||'#6b7280') + ';padding:.25rem .75rem;border-radius:1rem;font-size:.8rem;border:1px solid ' + (theme.colors.border||'#e5e7eb')">Category</span>
					</div>
					<a :style="'color:' + (theme.colors.accent||'#2563eb') + ';margin-top:1rem;display:inline-block'" href="#">
						Read more →
					</a>
				</div>
			</div>
		</div>

		<!-- JSON debug (collapsible) -->
		<details class="mt-3">
			<summary class="text-muted" style="cursor:pointer;font-size:.85rem"><?= Text::_('FLEXI_PROTHEME_JSON_DEBUG') ?></summary>
			<pre class="mt-2 p-2 bg-light rounded" style="font-size:.75rem;max-height:200px;overflow:auto" x-text="JSON.stringify(theme, null, 2)"></pre>
		</details>
	</div>

	<input type="hidden" name="task" value="">
</form>

<script>
function fcptThemeEditor() {
    const defaults = {
        colors: { accent: '#2563eb', text: '#172033', muted: '#6b7280', surface: '#ffffff', border: '#e5e7eb' },
        fontFamily: 'Inter',
        fontSize: '16px',
        headingFontFamily: '',
        headingFontWeight: '700'
    };

    const stored = <?= json_encode(json_decode($themeJson, true) ?: [], JSON_UNESCAPED_UNICODE) ?>;

    return {
        theme: {
            colors: Object.assign({}, defaults.colors, stored.colors || {}),
            fontFamily: stored.fontFamily || defaults.fontFamily,
            fontSize: stored.fontSize || defaults.fontSize,
            headingFontFamily: stored.headingFontFamily || defaults.headingFontFamily,
            headingFontWeight: stored.headingFontWeight || defaults.headingFontWeight
        },

        init() {
            const form = document.getElementById('adminForm');
            if (form) {
                form.addEventListener('submit', () => {
                    document.getElementById('fcpt_theme_data_input').value = JSON.stringify(this.theme);
                });
            }
        },

        bodyFontCss() {
            const f = (this.theme.fontFamily || '').trim();
            return f ? '"' + f.replace(/[^a-zA-Z0-9\s,\-]/g, '') + '", sans-serif' : 'inherit';
        },
        headingFontCss() {
            const f = (this.theme.headingFontFamily || this.theme.fontFamily || '').trim();
            return f ? '"' + f.replace(/[^a-zA-Z0-9\s,\-]/g, '') + '", sans-serif' : 'inherit';
        },
        previewStyle() {
            return {
                background: this.theme.colors.surface || '#ffffff',
                borderColor: this.theme.colors.border || '#e5e7eb',
                fontFamily: this.bodyFontCss()
            };
        }
    };
}
</script>
