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

require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'models' . DS . 'protheme.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'models' . DS . 'prothemes.php';

/**
 * Pro Themes Controller — handles list, CRUD, AJAX save.
 */
#[AllowDynamicProperties]
class FlexicontentControllerProthemes extends FlexicontentControllerBaseAdmin
{
	var $records_dbtbl  = 'flexicontent_pro_themes';
	var $records_jtable = 'flexicontent_pro_themes';

	var $record_name    = 'protheme';
	var $record_name_pl = 'prothemes';

	var $_NAME = 'PROTHEME';

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
	// AJAX: Save theme JSON from the theme editor
	// -------------------------------------------------------------------------

	/**
	 * task=prothemes.saveJson
	 * POST: id, theme_json, {token}=1
	 */
	public function saveJson(): void
	{
		$app    = Factory::getApplication();
		$jinput = $app->input;

		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$id   = (int) $jinput->getInt('id', 0);
		$json = $jinput->getRaw('theme_json', '');

		/** @var FlexicontentModelProtheme $model */
		$model = $this->getModel('protheme', '', []);

		$success = $model->saveThemeData($id, $json);

		echo new JsonResponse(
			$success ? ['id' => $id] : null,
			$success ? '' : $model->getError(),
			!$success
		);

		$app->close();
	}

	public function getModel($name = 'protheme', $prefix = '', $config = [])
	{
		$name = strtolower($name);

		if ($name === 'prothemes') {
			return new FlexicontentModelProthemes($config);
		}

		return new FlexicontentModelProtheme($config);
	}
}
