<?php
/**
 * @package         FLEXIcontent
 * @subpackage      Module Presets — edit template
 *
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var FlexicontentViewModulepreset $this */

$item = $this->item;
$id   = (int) ($item->id ?? 0);

// Pretty-print the stored params_json for display in the textarea
$rawParams = $item->params_json ?? '';
if ($rawParams !== '') {
	$decoded = json_decode($rawParams, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		$rawParams = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}
?>

<form action="<?= Route::_('index.php?option=com_flexicontent&view=modulepresets&layout=edit&id=' . $id) ?>"
      method="post" name="adminForm" id="adminForm">

	<input type="hidden" name="jform[id]" value="<?= $id ?>">
	<?= HTMLHelper::_('form.token') ?>

	<!-- Two-column layout: main fields left, meta fields right -->
	<div class="row g-3">

		<!-- Main column -->
		<div class="col-lg-8">

			<!-- Title -->
			<div class="card mb-3">
				<div class="card-body">
					<?= $this->form->renderField('title') ?>
					<?= $this->form->renderField('description') ?>
				</div>
			</div>

			<!-- JSON Params -->
			<div class="card mb-3">
				<div class="card-header d-flex align-items-center gap-2">
					<span class="icon-code" aria-hidden="true"></span>
					<strong>JSON Params</strong>
				</div>
				<div class="card-body">

					<!-- Tip notice -->
					<div class="alert alert-info d-flex gap-2 align-items-start py-2 mb-3" role="alert">
						<span class="icon-info-circle flex-shrink-0 mt-1" aria-hidden="true"></span>
						<div>
							<strong>Tip:</strong>
							Copy params from an existing module by navigating to its edit URL and appending
							<code>?format=json</code>, or paste an exported JSON preset here.
							Leave blank to fill via the module builder.
						</div>
					</div>

					<!-- Override the rendered field with the pretty-printed version -->
					<div class="control-group">
						<div class="control-label">
							<label for="jform_params_json" class="form-label">
								<?= Text::_('FLEXI_MODULEPRESET_FIELD_PARAMS_JSON_LABEL') ?>
							</label>
						</div>
						<div class="controls">
							<textarea id="jform_params_json"
							          name="jform[params_json]"
							          class="form-control font-monospace"
							          rows="14"
							          spellcheck="false"><?= htmlspecialchars($rawParams, ENT_QUOTES) ?></textarea>
							<small class="form-text text-muted">
								<?= Text::_('FLEXI_MODULEPRESET_FIELD_PARAMS_JSON_DESC') ?>
							</small>
						</div>
					</div>

				</div>
			</div>

		</div><!-- /main column -->

		<!-- Sidebar column -->
		<div class="col-lg-4">

			<div class="card mb-3">
				<div class="card-header"><strong><?= Text::_('JDETAILS') ?></strong></div>
				<div class="card-body">
					<?= $this->form->renderField('layout') ?>
					<?= $this->form->renderField('tags') ?>
					<?= $this->form->renderField('state') ?>
					<?= $this->form->renderField('ordering') ?>
				</div>
			</div>

			<?php if ($id > 0) : ?>
			<div class="card mb-3">
				<div class="card-header"><strong>Export</strong></div>
				<div class="card-body">
					<p class="text-muted small mb-2">
						Download this preset as a <code>.json</code> file to import into another site.
					</p>
					<a href="<?= \Joomla\CMS\Router\Route::_('index.php?option=com_flexicontent&task=modulepresets.export&id=' . $id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1') ?>"
					   class="btn btn-outline-secondary btn-sm w-100">
						<span class="icon-download" aria-hidden="true"></span>
						Download JSON
					</a>
				</div>
			</div>
			<?php endif; ?>

		</div><!-- /sidebar column -->

	</div>

	<input type="hidden" name="task" value="">
</form>
