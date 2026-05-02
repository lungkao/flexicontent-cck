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
use Joomla\CMS\Table\Table;

require_once __DIR__ . '/base/base.php';

/**
 * Module Presets — single preset model (CRUD)
 */
#[AllowDynamicProperties]
class FlexicontentModelModulepreset extends FCModelAdmin
{
	protected $name = 'modulepreset';

	var $records_dbtbl  = 'flexicontent_module_presets';
	var $records_jtable = 'flexicontent_module_presets';
	var $state_col      = 'state';
	var $name_col       = 'title';
	var $parent_col     = null;

	var $_id     = null;
	var $_record = null;

	// -------------------------------------------------------------------------

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_flexicontent.modulepreset',
			JPATH_ADMINISTRATOR . '/components/com_flexicontent/forms/modulepreset.xml',
			['control' => 'jform', 'load_data' => $loadData]
		);

		return $form ?: false;
	}

	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_flexicontent.edit.modulepreset.data', []);

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

	public function canEdit($record = null, $user = null)
	{
		if ($user) {
			throw new \Exception(__FUNCTION__ . '(): Error model does not support checking ACL of specific user', 500);
		}
		$user = Factory::getUser();
		return $user->authorise('flexicontent.managetemplates', 'com_flexicontent')
		    || $user->authorise('core.admin', 'com_flexicontent');
	}

	public function canEditState($record = null, $user = null)
	{
		return $this->canEdit($record);
	}

	public function canDelete($record = null)
	{
		return $this->canEdit();
	}

	public function getTable($type = 'flexicontent_module_presets', $prefix = '', $config = [])
	{
		return Table::getInstance($type, '', $config);
	}

	// -------------------------------------------------------------------------
	// Export / Import helpers
	// -------------------------------------------------------------------------

	/**
	 * Export a preset record as a JSON string suitable for download.
	 *
	 * @param  int    $id  Preset record id.
	 * @return string      JSON string, or empty string on failure.
	 */
	public function exportJson(int $id): string
	{
		if ($id <= 0) {
			$this->setError('Invalid preset id');
			return '';
		}

		$item = $this->getItem($id);

		if (!$item) {
			$this->setError('Preset not found');
			return '';
		}

		$export = [
			'title'       => $item->title,
			'description' => $item->description,
			'layout'      => $item->layout,
			'params_json' => $item->params_json,
			'tags'        => $item->tags,
		];

		return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Import a preset from a JSON string, saving it as a new record.
	 *
	 * @param  string $json  Raw JSON string.
	 * @return bool          True on success.
	 */
	public function importFromJson(string $json): bool
	{
		$data = json_decode($json, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->setError('Invalid JSON: ' . json_last_error_msg());
			return false;
		}

		$required = ['title', 'layout', 'params_json'];
		foreach ($required as $key) {
			if (empty($data[$key])) {
				$this->setError('Missing required field: ' . $key);
				return false;
			}
		}

		$user = Factory::getUser();
		$now  = Factory::getDate()->toSql();

		$saveData = [
			'id'          => 0,
			'title'       => (string) $data['title'],
			'description' => isset($data['description']) ? (string) $data['description'] : '',
			'layout'      => (string) $data['layout'],
			'params_json' => (string) $data['params_json'],
			'tags'        => isset($data['tags']) ? (string) $data['tags'] : '',
			'state'       => 1,
			'ordering'    => 0,
			'created'     => $now,
			'created_by'  => (int) $user->id,
			'modified'    => $now,
			'modified_by' => (int) $user->id,
		];

		return $this->save($saveData);
	}
}
