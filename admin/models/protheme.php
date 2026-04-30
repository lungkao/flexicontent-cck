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
use Joomla\CMS\Table\Table;

require_once __DIR__ . '/base/base.php';

/**
 * Pro Themes — single theme model (CRUD)
 */
#[AllowDynamicProperties]
class FlexicontentModelProtheme extends FCModelAdmin
{
	protected $name = 'protheme';

	var $records_dbtbl  = 'flexicontent_pro_themes';
	var $records_jtable = 'flexicontent_pro_themes';
	var $state_col      = 'state';
	var $name_col       = 'title';
	var $parent_col     = null;

	var $_id     = null;
	var $_record = null;

	// -------------------------------------------------------------------------

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_flexicontent.protheme',
			JPATH_ADMINISTRATOR . '/components/com_flexicontent/forms/protheme.xml',
			['control' => 'jform', 'load_data' => $loadData]
		);

		return $form ?: false;
	}

	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_flexicontent.edit.protheme.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		$pk    = $pk ?? (int) $this->getState($this->getName() . '.id');
		$table = $this->getTable();

		if ($pk > 0) {
			if (!$table->load($pk)) {
				$this->setError($table->getError());
				return false;
			}
		}

		$properties = $table->getProperties(1);
		return \Joomla\Utilities\ArrayHelper::toObject($properties, \stdClass::class);
	}

	/**
	 * Save theme_data JSON directly (from the theme editor).
	 *
	 * @param  int    $id        Theme record id.
	 * @param  string $themeJson Raw JSON string.
	 * @return bool
	 */
	public function saveThemeData(int $id, string $themeJson): bool
	{
		if ($id <= 0) {
			$this->setError('Invalid theme id');
			return false;
		}

		json_decode($themeJson);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->setError('Invalid JSON: ' . json_last_error_msg());
			return false;
		}

		$user = Factory::getUser();
		$db   = Factory::getDbo();
		$now  = Factory::getDate()->toSql();

		$query = $db->getQuery(true)
			->update('#__flexicontent_pro_themes')
			->set($db->quoteName('theme_data')  . ' = ' . $db->quote($themeJson))
			->set($db->quoteName('modified')    . ' = ' . $db->quote($now))
			->set($db->quoteName('modified_by') . ' = ' . (int) $user->id)
			->where($db->quoteName('id') . ' = ' . (int) $id);

		$db->setQuery($query)->execute();

		return true;
	}

	public function getTable($type = 'flexicontent_pro_themes', $prefix = '', $config = [])
	{
		return Table::getInstance($type, '', $config);
	}
}
