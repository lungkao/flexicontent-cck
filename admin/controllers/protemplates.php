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

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

JLoader::register('FlexicontentControllerBaseAdmin', JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'controllers' . DS . 'base' . DS . 'baseadmin.php');

require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'models' . DS . 'protemplate.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'models' . DS . 'protemplates.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'helpers' . DS . 'protemplate' . DS . 'LicenseManager.php';

/**
 * Pro Templates Controller — handles list, CRUD, AJAX save / autosave.
 */
#[AllowDynamicProperties]
class FlexicontentControllerProtemplates extends FlexicontentControllerBaseAdmin
{
	var $records_dbtbl  = 'flexicontent_pro_layouts';
	var $records_jtable = 'flexicontent_pro_layouts';

	var $record_name    = 'protemplate';
	var $record_name_pl = 'protemplates';

	var $_NAME = 'PROTEMPLATE';

	var $runMode = 'standalone';

	var $exitHttpHead = null;
	var $exitMessages = [];
	var $exitLogTexts = [];
	var $exitSuccess  = true;

	public function __construct($config = [])
	{
		parent::__construct($config);
	}

	// -------------------------------------------------------------------------
	// AJAX: Save full layout JSON (called on Save button from builder)
	// -------------------------------------------------------------------------

	/**
	 * task=protemplates.saveJson
	 * POST: id, layout_json, {token}=1
	 */
	public function saveJson(): void
	{
		/** @var \Joomla\CMS\Application\AdministratorApplication $app */
		$app    = Factory::getApplication();
		$jinput = $app->input;

		// CSRF check
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$id   = (int) $jinput->getInt('id', 0);
		$json = $jinput->getRaw('layout_json', '');

		/** @var FlexicontentModelProtemplate $model */
		$model = $this->getModel('protemplate', '', []);

		$success = $model->saveLayoutData($id, $json);

		if ($success) {
			$model->storeRevision($id, $json, 'manual');
		}

		echo new JsonResponse(
			$success ? ['id' => $id] : null,
			$success ? '' : $model->getError(),
			!$success
		);

		$app->close();
	}

	// -------------------------------------------------------------------------
	// AJAX: Autosave draft (called automatically every ~1.2 s after changes)
	// -------------------------------------------------------------------------

	/**
	 * task=protemplates.autosaveJson
	 * POST: id, layout_json, {token}=1
	 */
	public function autosaveJson(): void
	{
		$app    = Factory::getApplication();
		$jinput = $app->input;

		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$id   = (int) $jinput->getInt('id', 0);
		$json = $jinput->getRaw('layout_json', '');

		/** @var FlexicontentModelProtemplate $model */
		$model = $this->getModel('protemplate', '', []);

		$success = $model->storeRevision($id, $json, 'autosave');

		echo new JsonResponse(
			$success ? ['id' => $id] : null,
			$success ? '' : 'Autosave failed',
			!$success
		);

		$app->close();
	}

	// -------------------------------------------------------------------------
	// Standard helpers
	// -------------------------------------------------------------------------

	public function getModel($name = 'protemplate', $prefix = '', $config = [])
	{
		$name = strtolower($name);

		if ($name === 'protemplates') {
			return new FlexicontentModelProtemplates($config);
		}

		return new FlexicontentModelProtemplate($config);
	}
}
