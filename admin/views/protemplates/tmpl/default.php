<?php
/**
 * @package         FLEXIcontent
 * @subpackage      Pro Templates — list template
 *
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$app    = Factory::getApplication();
$user   = Factory::getUser();
$ctrl   = 'protemplates.';
$token  = Session::getFormToken();
?>

<div class="fc-protemplate-list-head mb-3">
	<div class="alert alert-info d-flex align-items-center gap-2" style="border-left:4px solid #6366f1">
		<span style="font-size:1.4rem">⭐</span>
		<div>
			<strong><?= Text::_('FLEXI_PROTEMPLATE_PRO_FEATURE') ?></strong>
			&nbsp;—&nbsp;<?= Text::_('FLEXI_PROTEMPLATE_LIST_INTRO') ?>
			&nbsp;<a href="<?= Route::_('index.php?option=com_flexicontent&view=prothemes') ?>" class="alert-link">
				<?= Text::_('FLEXI_PROTHEME_MANAGE') ?> →
			</a>
		</div>
	</div>
</div>

<form action="<?= Route::_('index.php?option=com_flexicontent&view=protemplates') ?>"
	  method="post" name="adminForm" id="adminForm">

	<!-- Search & filters -->
	<div class="row mb-3 g-2">
		<div class="col-md-4">
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
			<button type="submit" class="btn btn-primary"><?= Text::_('JSEARCH_FILTER_SUBMIT') ?></button>
			<a href="<?= Route::_('index.php?option=com_flexicontent&view=protemplates') ?>"
			   class="btn btn-outline-secondary"><?= Text::_('JSEARCH_FILTER_CLEAR') ?></a>
		</div>
		<div class="col-md-4 text-end">
			<a href="<?= Route::_('index.php?option=com_flexicontent&view=prothemes') ?>"
			   class="btn btn-outline-primary btn-sm">
				🎨 <?= Text::_('FLEXI_PROTHEME_MANAGE') ?>
			</a>
		</div>
	</div>

	<!-- Table -->
	<table class="table table-striped table-hover" id="fc-protemplate-list">
		<thead>
			<tr>
				<th style="width:1%">
					<?= HTMLHelper::_('grid.checkall') ?>
				</th>
				<th><?= Text::_('FLEXI_TITLE') ?></th>
				<th style="width:12%"><?= Text::_('FLEXI_TYPE') ?></th>
				<th style="width:12%"><?= Text::_('FLEXI_CATEGORY') ?></th>
				<th style="width:12%"><?= Text::_('FLEXI_PROTEMPLATE_ASSIGNMENT_TYPE') ?></th>
				<th style="width:8%"><?= Text::_('JSTATUS') ?></th>
				<th style="width:6%"><?= Text::_('JGRID_HEADING_ORDERING') ?></th>
				<th style="width:6%"><?= Text::_('JGRID_HEADING_ID') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (empty($this->rows)) : ?>
			<tr>
				<td colspan="8" class="text-center text-muted py-4">
					<?= Text::_('FLEXI_PROTEMPLATE_NO_LAYOUTS') ?>
					&nbsp;<a href="<?= Route::_('index.php?option=com_flexicontent&view=protemplate&layout=edit') ?>">
						<?= Text::_('FLEXI_CREATE_FIRST') ?>
					</a>
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ($this->rows as $i => $row) : ?>
			<tr>
				<td><?= HTMLHelper::_('grid.id', $i, $row->id) ?></td>
				<td>
					<a href="<?= Route::_('index.php?option=com_flexicontent&view=protemplate&layout=edit&id=' . (int) $row->id) ?>">
						<?= htmlspecialchars($row->title, ENT_QUOTES) ?>
					</a>
					<?php if ($row->note) : ?>
						<br><small class="text-muted"><?= htmlspecialchars($row->note, ENT_QUOTES) ?></small>
					<?php endif; ?>
				</td>
				<td><?= htmlspecialchars($row->type_name ?? Text::_('FLEXI_PROTEMPLATE_ALL_TYPES'), ENT_QUOTES) ?></td>
				<td><?= htmlspecialchars($row->cat_name  ?? Text::_('FLEXI_PROTEMPLATE_ALL_CATS'),  ENT_QUOTES) ?></td>
				<td>
					<span class="badge bg-secondary">
						<?= Text::_('FLEXI_PROTEMPLATE_ASSIGN_' . strtoupper($row->assignment_type)) ?>
					</span>
				</td>
				<td>
					<?= HTMLHelper::_('jgrid.published', $row->state, $i, $ctrl, true) ?>
				</td>
				<td>
					<?= $row->ordering ?>
				</td>
				<td><?= (int) $row->id ?></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?= $this->pagination->getListFooter() ?>

	<input type="hidden" name="task"   value="">
	<input type="hidden" name="boxchecked" value="0">
	<?= HTMLHelper::_('form.token') ?>
</form>
