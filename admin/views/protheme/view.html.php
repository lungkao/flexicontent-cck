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

require_once JPATH_ADMINISTRATOR . '/components/com_flexicontent/models/protheme.php';

/**
 * Pro Theme — Editor View
 */
#[AllowDynamicProperties]
class FlexicontentViewProtheme extends HtmlView
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

		/** @var FlexicontentModelProtheme $model */
		$model = new FlexicontentModelProtheme();
		$model->setState('protheme.id', $id);

		$this->item = $model->getItem($id);
		$this->form = $model->getForm([], true);

		if ($this->item && $this->form) {
			$this->form->bind((array) $this->item);
		}

		$isNew = ($id === 0);
		ToolbarHelper::title(
			'<span class="fc-pro-badge">⭐</span> ' .
			Text::_($isNew ? 'FLEXI_PROTHEME_NEW' : 'FLEXI_PROTHEME_EDIT'),
			'paintbrush'
		);
		ToolbarHelper::apply('prothemes.apply');
		ToolbarHelper::save('prothemes.save');
		ToolbarHelper::cancel('prothemes.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

		parent::display($tpl);
	}
}
