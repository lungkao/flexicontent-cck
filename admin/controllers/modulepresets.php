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
use Joomla\CMS\Session\Session;

JLoader::register('FlexicontentControllerBaseAdmin', JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'controllers' . DS . 'base' . DS . 'baseadmin.php');

require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'models' . DS . 'modulepreset.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'models' . DS . 'modulepresets.php';

/**
 * Module Presets Controller — handles list, CRUD, export and import.
 */
#[AllowDynamicProperties]
class FlexicontentControllerModulepresets extends FlexicontentControllerBaseAdmin
{
	var $records_dbtbl  = 'flexicontent_module_presets';
	var $records_jtable = 'flexicontent_module_presets';

	var $record_name    = 'modulepreset';
	var $record_name_pl = 'modulepresets';

	var $_NAME = 'MODULEPRESET';

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
	// Export: send preset as a JSON file download
	// -------------------------------------------------------------------------

	/**
	 * task=modulepresets.export
	 * GET: id, {token}=1
	 */
	public function export(): void
	{
		$app    = Factory::getApplication();
		$jinput = $app->input;

		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$id = (int) $jinput->getInt('id', 0);

		if ($id <= 0) {
			$app->enqueueMessage(Text::_('FLEXI_MODULEPRESET_IMPORT_FAILED'), 'error');
			$app->redirect('index.php?option=com_flexicontent&view=modulepresets');
			return;
		}

		/** @var FlexicontentModelModulepreset $model */
		$model = $this->getModel('modulepreset', '', []);
		$json  = $model->exportJson($id);

		if ($json === '') {
			$app->enqueueMessage($model->getError() ?: Text::_('FLEXI_MODULEPRESET_IMPORT_FAILED'), 'error');
			$app->redirect('index.php?option=com_flexicontent&view=modulepresets');
			return;
		}

		$filename = 'fcmod-preset-' . $id . '.json';

		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Length: ' . strlen($json));

		echo $json;

		$app->close();
	}

	// -------------------------------------------------------------------------
	// Import: read an uploaded JSON file and save as a new preset
	// -------------------------------------------------------------------------

	/**
	 * task=modulepresets.import
	 * POST: preset_json (file upload), {token}=1
	 */
	public function import(): void
	{
		$app = Factory::getApplication();

		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$file = $_FILES['preset_json'] ?? null;

		if (empty($file) || !empty($file['error']) || empty($file['tmp_name'])) {
			$app->enqueueMessage(Text::_('FLEXI_MODULEPRESET_IMPORT_FAILED'), 'error');
			$app->redirect('index.php?option=com_flexicontent&view=modulepresets');
			return;
		}

		$json = file_get_contents($file['tmp_name']);

		if ($json === false || trim($json) === '') {
			$app->enqueueMessage(Text::_('FLEXI_MODULEPRESET_IMPORT_FAILED'), 'error');
			$app->redirect('index.php?option=com_flexicontent&view=modulepresets');
			return;
		}

		/** @var FlexicontentModelModulepreset $model */
		$model   = $this->getModel('modulepreset', '', []);
		$success = $model->importFromJson($json);

		if ($success) {
			$app->enqueueMessage(Text::_('FLEXI_MODULEPRESET_IMPORTED'), 'message');
		} else {
			$app->enqueueMessage(
				Text::_('FLEXI_MODULEPRESET_IMPORT_FAILED') . ' ' . $model->getError(),
				'error'
			);
		}

		$app->redirect('index.php?option=com_flexicontent&view=modulepresets');
	}

	// -------------------------------------------------------------------------
	// Standard helpers
	// -------------------------------------------------------------------------

	public function getModel($name = 'modulepreset', $prefix = '', $config = [])
	{
		$name = strtolower($name);

		if ($name === 'modulepresets') {
			return new FlexicontentModelModulepresets($config);
		}

		return new FlexicontentModelModulepreset($config);
	}
}
