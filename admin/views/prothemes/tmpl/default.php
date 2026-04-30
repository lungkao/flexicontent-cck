<?php
/**
 * @package         FLEXIcontent
 * @subpackage      Pro Themes — list template
 *
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$ctrl = 'prothemes.';
?>

<form action="<?= Route::_('index.php?option=com_flexicontent&view=prothemes') ?>"
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
			<a href="<?= Route::_('index.php?option=com_flexicontent&view=prothemes') ?>"
			   class="btn btn-outline-secondary"><?= Text::_('JSEARCH_FILTER_CLEAR') ?></a>
		</div>
		<div class="col-md-4 text-end">
			<a href="<?= Route::_('index.php?option=com_flexicontent&view=protemplates') ?>"
			   class="btn btn-outline-primary btn-sm">
				← <?= Text::_('FLEXI_PROTEMPLATE_MANAGER') ?>
			</a>
		</div>
	</div>

	<!-- Table -->
	<table class="table table-striped table-hover" id="fc-protheme-list">
		<thead>
			<tr>
				<th style="width:1%"><?= HTMLHelper::_('grid.checkall') ?></th>
				<th><?= Text::_('FLEXI_TITLE') ?></th>
				<th style="width:12%"><?= Text::_('JSTATUS') ?></th>
				<th style="width:8%"><?= Text::_('JGRID_HEADING_ORDERING') ?></th>
				<th style="width:6%"><?= Text::_('JGRID_HEADING_ID') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (empty($this->rows)) : ?>
			<tr>
				<td colspan="5" class="text-center text-muted py-4">
					<?= Text::_('FLEXI_PROTHEME_NO_THEMES') ?>
					&nbsp;<a href="<?= Route::_('index.php?option=com_flexicontent&view=protheme&layout=edit') ?>">
						<?= Text::_('FLEXI_CREATE_FIRST') ?>
					</a>
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ($this->rows as $i => $row) : ?>
			<tr>
				<td><?= HTMLHelper::_('grid.id', $i, $row->id) ?></td>
				<td>
					<a href="<?= Route::_('index.php?option=com_flexicontent&view=protheme&layout=edit&id=' . (int) $row->id) ?>">
						<?= htmlspecialchars($row->title, ENT_QUOTES) ?>
					</a>
					<?php
					// Show accent swatch if theme_data has colors
					$td = json_decode($row->theme_data ?? '{}', true);
					$accent = $td['colors']['accent'] ?? '';
					if ($accent && preg_match('/^#[0-9a-fA-F]{3,8}$/', $accent)) :
					?>
					<span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:<?= htmlspecialchars($accent, ENT_QUOTES) ?>;vertical-align:middle;margin-left:6px;border:1px solid #ccc"></span>
					<?php endif; ?>
				</td>
				<td><?= HTMLHelper::_('jgrid.published', $row->state, $i, $ctrl, true) ?></td>
				<td><?= (int) $row->ordering ?></td>
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
