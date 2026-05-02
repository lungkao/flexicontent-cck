<?php
/**
 * @package         FLEXIcontent
 * @subpackage      Module Presets — list template
 *
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$ctrl  = 'modulepresets.';
$token = Session::getFormToken();

// Layout badge colour map
$layoutBadge = [
	'modern'   => 'bg-primary',
	'news'     => 'bg-success',
	'carousel' => 'bg-warning text-dark',
	'default'  => 'bg-secondary',
];
?>

<form action="<?= Route::_('index.php?option=com_flexicontent&view=modulepresets') ?>"
      method="post" name="adminForm" id="adminForm">

	<!-- Search & filters -->
	<div class="row mb-3 g-2 align-items-center">
		<div class="col-md-3">
			<input type="text"
			       name="filter_search"
			       class="form-control"
			       placeholder="<?= Text::_('JSEARCH_FILTER_SUBMIT') ?>"
			       value="<?= htmlspecialchars($this->state->get('filter.search', ''), ENT_QUOTES) ?>">
		</div>
		<div class="col-md-2">
			<?= $this->lists['state_filter'] ?? '' ?>
		</div>
		<div class="col-md-2">
			<?= $this->lists['layout_filter'] ?? '' ?>
		</div>
		<div class="col-md-3">
			<button type="submit" class="btn btn-primary"><?= Text::_('JSEARCH_FILTER_SUBMIT') ?></button>
			<a href="<?= Route::_('index.php?option=com_flexicontent&view=modulepresets') ?>"
			   class="btn btn-outline-secondary"><?= Text::_('JSEARCH_FILTER_CLEAR') ?></a>
		</div>
		<div class="col-md-2 text-end">
			<a href="<?= Route::_('index.php?option=com_flexicontent&view=protemplates') ?>"
			   class="btn btn-outline-primary btn-sm">
				&larr; <?= Text::_('FLEXI_PROTEMPLATE_MANAGER') ?>
			</a>
		</div>
	</div>

	<!-- Table -->
	<table class="table table-striped table-hover" id="fc-modulepreset-list">
		<thead>
			<tr>
				<th style="width:1%"><?= HTMLHelper::_('grid.checkall') ?></th>
				<th><?= Text::_('FLEXI_TITLE') ?></th>
				<th style="width:12%">Layout</th>
				<th style="width:18%">Tags</th>
				<th style="width:10%"><?= Text::_('JSTATUS') ?></th>
				<th style="width:8%"><?= Text::_('JGRID_HEADING_ORDERING') ?></th>
				<th style="width:8%">Export</th>
				<th style="width:6%"><?= Text::_('JGRID_HEADING_ID') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (empty($this->rows)) : ?>
			<tr>
				<td colspan="8" class="text-center text-muted py-4">
					<?= Text::_('FLEXI_NO_RECORDS_FOUND') ?>
					&nbsp;<a href="<?= Route::_('index.php?option=com_flexicontent&view=modulepreset&layout=edit') ?>">
						<?= Text::_('FLEXI_CREATE_FIRST') ?>
					</a>
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ($this->rows as $i => $row) : ?>
			<tr>
				<td><?= HTMLHelper::_('grid.id', $i, $row->id) ?></td>
				<td>
					<a href="<?= Route::_('index.php?option=com_flexicontent&view=modulepreset&layout=edit&id=' . (int) $row->id) ?>">
						<?= htmlspecialchars($row->title, ENT_QUOTES) ?>
					</a>
					<?php if (!empty($row->description)) : ?>
					<small class="d-block text-muted"><?= htmlspecialchars(mb_strimwidth($row->description, 0, 80, '…'), ENT_QUOTES) ?></small>
					<?php endif; ?>
				</td>
				<td>
					<?php
					$layout = htmlspecialchars($row->layout, ENT_QUOTES);
					$badge  = $layoutBadge[$row->layout] ?? 'bg-secondary';
					?>
					<span class="badge <?= $badge ?>"><?= $layout ?></span>
				</td>
				<td>
					<?php if (!empty($row->tags)) : ?>
						<?php foreach (array_filter(array_map('trim', explode(',', $row->tags))) as $tag) : ?>
						<span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($tag, ENT_QUOTES) ?></span>
						<?php endforeach; ?>
					<?php endif; ?>
				</td>
				<td><?= HTMLHelper::_('jgrid.published', $row->state, $i, $ctrl, true) ?></td>
				<td><?= (int) $row->ordering ?></td>
				<td>
					<a href="<?= Route::_('index.php?option=com_flexicontent&task=modulepresets.export&id=' . (int) $row->id . '&' . $token . '=1') ?>"
					   class="btn btn-outline-secondary btn-sm"
					   title="<?= Text::_('FLEXI_MODULEPRESET_EXPORTED') ?>">
						<span class="icon-download" aria-hidden="true"></span>
						JSON
					</a>
				</td>
				<td><?= (int) $row->id ?></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?= $this->pagination->getListFooter() ?>

	<input type="hidden" name="task"       value="">
	<input type="hidden" name="boxchecked" value="0">
	<?= HTMLHelper::_('form.token') ?>
</form>
