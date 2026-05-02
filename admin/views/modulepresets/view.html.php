<?php
/**
 * @package         FLEXIcontent
 * @subpackage      Module Presets
 *
 * @author          FLEXIcontent Team
 * @link            https://flexicontent.org
 * @copyright       Copyright © 2024, FLEXIcontent team, All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::register('FlexicontentViewBaseRecords', JPATH_ADMINISTRATOR . '/components/com_flexicontent/helpers/base/view_records.php');

/**
 * Module Presets — List View
 */
#[AllowDynamicProperties]
class FlexicontentViewModulepresets extends FlexicontentViewBaseRecords
{
	public mixed $rows       = null;
	public mixed $pagination = null;
	public mixed $state      = null;
	public mixed $lists      = null;

	var $title_propname = 'title';
	var $state_propname = 'state';
	var $db_tbl         = 'flexicontent_module_presets';
	var $name_singular  = 'modulepreset';

	public function display($tpl = null)
	{
		$app = Factory::getApplication();

		/** @var FlexicontentModelModulepresets $model */
		$model = $this->getModel();

		$this->rows       = $model->getData();
		$this->pagination = $model->getPagination();
		$this->state      = $model->getState();

		// State filter dropdown
		$this->lists['state_filter'] = HTMLHelper::_(
			'select.genericlist',
			[
				HTMLHelper::_('select.option', '', Text::_('JOPTION_SELECT_PUBLISHED')),
				HTMLHelper::_('select.option', '1', Text::_('JPUBLISHED')),
				HTMLHelper::_('select.option', '0', Text::_('JUNPUBLISHED')),
			],
			'filter_state',
			'class="form-select"',
			'value', 'text',
			$this->state->get('filter.state', '')
		);

		// Layout filter dropdown
		$this->lists['layout_filter'] = HTMLHelper::_(
			'select.genericlist',
			[
				HTMLHelper::_('select.option', '',          Text::_('JOPTION_SELECT_PUBLISHED')), // reuse "- Select -"
				HTMLHelper::_('select.option', 'modern',    'Modern'),
				HTMLHelper::_('select.option', 'news',      'News'),
				HTMLHelper::_('select.option', 'carousel',  'Carousel'),
				HTMLHelper::_('select.option', 'default',   'Default'),
			],
			'filter_layout',
			'class="form-select"',
			'value', 'text',
			$this->state->get('filter.layout', '')
		);

		ToolbarHelper::title(Text::_('FLEXI_MODULE_PRESETS_MANAGER'), 'puzzle-piece');
		ToolbarHelper::addNew('modulepresets.add');
		ToolbarHelper::editList('modulepresets.edit');
		ToolbarHelper::divider();
		ToolbarHelper::publishList('modulepresets.publish');
		ToolbarHelper::unpublishList('modulepresets.unpublish');
		ToolbarHelper::divider();
		ToolbarHelper::deleteList(Text::_('FLEXI_CONFIRM_DELETE'), 'modulepresets.remove');

		// Custom Import button — links to the import layout
		$toolbar = \Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
		$toolbar->appendButton(
			'Link',
			'upload',
			'FLEXI_MODULEPRESET_IMPORT_BTN',
			Route::_('index.php?option=com_flexicontent&view=modulepresets&layout=import')
		);

		parent::display($tpl);
	}
}
