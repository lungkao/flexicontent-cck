<?php
/**
 * @package         FLEXIcontent
 * @subpackage      Pro Templates
 *
 * @author          FLEXIcontent Team
 * @link            https://flexicontent.org
 * @copyright       Copyright © 2024, FLEXIcontent team, All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;

require_once JPATH_ADMINISTRATOR . '/components/com_flexicontent/helpers/protemplate/LicenseManager.php';

JLoader::register('FlexicontentViewBaseRecords', JPATH_ADMINISTRATOR . '/components/com_flexicontent/helpers/base/view_records.php');

/**
 * Pro Templates — List View
 */
#[AllowDynamicProperties]
class FlexicontentViewProtemplates extends FlexicontentViewBaseRecords
{
	/** @var array $rows */
	public mixed $rows = null;
	/** @var object $pagination */
	public mixed $pagination = null;
	/** @var object $state */
	public mixed $state = null;
	/** @var array $lists */
	public mixed $lists = null;

	var $title_propname = 'title';
	var $state_propname = 'state';
	var $db_tbl         = 'flexicontent_pro_layouts';
	var $name_singular  = 'protemplate';

	public function display($tpl = null)
	{
		$app      = Factory::getApplication();
		$jinput   = $app->input;
		$document = Factory::getDocument();
		$user     = Factory::getUser();

		// Gate: Pro license
		if (!FlexicontentProLicenseManager::isLicensed()) {
			$app->enqueueMessage(Text::_('FLEXI_PROTEMPLATE_LICENSE_REQUIRED'), 'error');
			$app->redirect('index.php?option=com_flexicontent');
			return;
		}

		/** @var FlexicontentModelProtemplates $model */
		$model = $this->getModel();

		$this->rows       = $model->getData();
		$this->pagination = $model->getPagination();
		$this->state      = $model->getState();

		// Build filter lists
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

		// Toolbar
		ToolbarHelper::title(
			'<span class="fc-pro-badge">⭐</span> ' . Text::_('FLEXI_PROTEMPLATE_MANAGER'),
			'stack'
		);
		ToolbarHelper::addNew('protemplates.add');
		ToolbarHelper::editList('protemplates.edit');
		ToolbarHelper::divider();
		ToolbarHelper::publishList('protemplates.publish');
		ToolbarHelper::unpublishList('protemplates.unpublish');
		ToolbarHelper::divider();
		ToolbarHelper::deleteList(Text::_('FLEXI_CONFIRM_DELETE'), 'protemplates.remove');

		parent::display($tpl);
	}
}
