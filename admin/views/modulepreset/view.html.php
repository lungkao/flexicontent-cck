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
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView;

require_once JPATH_ADMINISTRATOR . '/components/com_flexicontent/models/modulepreset.php';

/**
 * Module Preset — Edit View
 */
#[AllowDynamicProperties]
class FlexicontentViewModulepreset extends HtmlView
{
	/** @var object $item */
	public mixed $item = null;

	/** @var \Joomla\CMS\Form\Form $form */
	public mixed $form = null;

	public function display($tpl = null)
	{
		$app    = Factory::getApplication();
		$jinput = $app->input;
		$id     = (int) $jinput->getInt('id', 0);

		/** @var FlexicontentModelModulepreset $model */
		$model = new FlexicontentModelModulepreset();
		$model->setState('modulepreset.id', $id);

		$this->item = $model->getItem($id);
		$this->form = $model->getForm([], true);

		if ($this->item && $this->form) {
			$this->form->bind((array) $this->item);
		}

		$isNew = ($id === 0);
		ToolbarHelper::title(
			Text::_($isNew ? 'FLEXI_MODULEPRESET_NEW' : 'FLEXI_MODULEPRESET_EDIT'),
			'puzzle-piece'
		);
		ToolbarHelper::apply('modulepresets.apply');
		ToolbarHelper::save('modulepresets.save');
		ToolbarHelper::cancel('modulepresets.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

		parent::display($tpl);
	}
}
