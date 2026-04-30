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
use Joomla\CMS\MVC\View\HtmlView;

require_once JPATH_ADMINISTRATOR . '/components/com_flexicontent/helpers/protemplate/LicenseManager.php';
require_once JPATH_ADMINISTRATOR . '/components/com_flexicontent/models/protemplate.php';

/**
 * Pro Template — Builder (edit) View
 */
#[AllowDynamicProperties]
class FlexicontentViewProtemplate extends HtmlView
{
	/** @var object $item */
	public mixed $item   = null;
	/** @var array $fields */
	public mixed $fields = null;
	/** @var array $themes */
	public mixed $themes = null;
	/** @var \Joomla\CMS\Form\Form $form */
	public mixed $form   = null;

	public function display($tpl = null)
	{
		$app    = Factory::getApplication();
		$jinput = $app->input;
		$id     = (int) $jinput->getInt('id', 0);

		// Gate: Pro license
		if (!FlexicontentProLicenseManager::isLicensed()) {
			$app->enqueueMessage(Text::_('FLEXI_PROTEMPLATE_LICENSE_REQUIRED'), 'error');
			$app->redirect('index.php?option=com_flexicontent');
			return;
		}

		/** @var FlexicontentModelProtemplate $model */
		$model = new FlexicontentModelProtemplate();
		$model->setState('protemplate.id', $id);

		$this->item   = $model->getItem($id);
		$this->form   = $model->getForm([], true);
		$this->themes = $model->getThemes();

		// Load fields for the layout's type_id
		$type_id      = (int) ($this->item->type_id ?? 0);
		$this->fields = $model->getFieldsForType($type_id);

		// Populate form with item data
		if ($this->item && $this->form) {
			$this->form->bind((array) $this->item);
		}

		// Toolbar
		$isNew = ($id === 0);
		ToolbarHelper::title(
			'<span class="fc-pro-badge">⭐</span> ' .
			Text::_($isNew ? 'FLEXI_PROTEMPLATE_NEW' : 'FLEXI_PROTEMPLATE_EDIT'),
			'stack'
		);
		ToolbarHelper::apply('protemplates.apply');
		ToolbarHelper::save('protemplates.save');
		ToolbarHelper::cancel('protemplates.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

		parent::display($tpl);
	}
}
