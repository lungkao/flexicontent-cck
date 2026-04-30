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
 * Pro Templates — single layout model (CRUD + builder helpers)
 */
#[AllowDynamicProperties]
class FlexicontentModelProtemplate extends FCModelAdmin
{
	/** @var string Record name — used to find XML form file */
	protected $name = 'protemplate';

	/** @var string DB table */
	var $records_dbtbl  = 'flexicontent_pro_layouts';

	/** @var string JTable class name */
	var $records_jtable = 'flexicontent_pro_layouts';

	/** @var string State column */
	var $state_col = 'state';

	/** @var string Name/label column */
	var $name_col  = 'title';

	/** @var null No parent column */
	var $parent_col = null;

	/** @var int Current record id */
	var $_id = null;

	/** @var object|null Loaded record */
	var $_record = null;

	// -------------------------------------------------------------------------
	// Overrides
	// -------------------------------------------------------------------------

	/**
	 * Get form — load admin/forms/protemplate.xml
	 *
	 * @param array $data
	 * @param bool  $loadData
	 * @return \Joomla\CMS\Form\Form|false
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_flexicontent.protemplate',
			JPATH_ADMINISTRATOR . '/components/com_flexicontent/forms/protemplate.xml',
			['control' => 'jform', 'load_data' => $loadData]
		);

		return $form ?: false;
	}

	/**
	 * Load data for the form (from session or DB).
	 *
	 * @return object
	 */
	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_flexicontent.edit.protemplate.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Get one layout record.
	 *
	 * @param int|null $pk
	 * @return \Joomla\CMS\Table\Table|false
	 */
	public function getItem($pk = null)
	{
		$pk = $pk ?? (int) $this->getState($this->getName() . '.id');
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

	// -------------------------------------------------------------------------
	// Fields & Themes helpers (used by the builder view)
	// -------------------------------------------------------------------------

	/**
	 * Get FLEXIcontent fields available for a given type_id.
	 *
	 * @param  int  $type_id  0 = all fields
	 * @return array
	 */
	public function getFieldsForType(int $type_id = 0): array
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('f.id, f.label, f.field_type AS type')
			->from('#__flexicontent_fields AS f');

		if ($type_id > 0) {
			$query->join('INNER', '#__flexicontent_fields_type_relations AS r ON r.field_id = f.id AND r.type_id = ' . (int) $type_id);
		}

		$query->where('f.published = 1')
			->order('f.label ASC');

		return $db->setQuery($query)->loadObjectList() ?: [];
	}

	/**
	 * Get all published Pro Themes.
	 *
	 * @return array
	 */
	public function getThemes(): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id, title, theme_data')
			->from('#__flexicontent_pro_themes')
			->where('state = 1')
			->order('ordering ASC, title ASC');

		return $db->setQuery($query)->loadObjectList() ?: [];
	}

	// -------------------------------------------------------------------------
	// JSON save / autosave
	// -------------------------------------------------------------------------

	/**
	 * Persist layout_data JSON and update modified stamps.
	 *
	 * @param  int    $id          Layout record id.
	 * @param  string $layoutJson  Raw JSON string from the builder.
	 * @return bool
	 */
	public function saveLayoutData(int $id, string $layoutJson): bool
	{
		if ($id <= 0) {
			$this->setError('Invalid layout id');
			return false;
		}

		// Validate JSON
		json_decode($layoutJson);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->setError('Invalid JSON: ' . json_last_error_msg());
			return false;
		}

		$user = Factory::getUser();
		$db   = Factory::getDbo();

		$now = Factory::getDate()->toSql();

		$query = $db->getQuery(true)
			->update('#__flexicontent_pro_layouts')
			->set($db->quoteName('layout_data') . ' = ' . $db->quote($layoutJson))
			->set($db->quoteName('modified')    . ' = ' . $db->quote($now))
			->set($db->quoteName('modified_by') . ' = ' . (int) $user->id)
			->where($db->quoteName('id') . ' = ' . (int) $id);

		$db->setQuery($query)->execute();

		return true;
	}

	/**
	 * Store an autosave revision without updating the main record.
	 *
	 * @param  int    $layoutId    Layout record id.
	 * @param  string $layoutJson  Raw JSON string.
	 * @return bool
	 */
	public function storeRevision(int $layoutId, string $layoutJson, string $type = 'autosave'): bool
	{
		if ($layoutId <= 0) return false;

		$user = Factory::getUser();
		$db   = Factory::getDbo();
		$now  = Factory::getDate()->toSql();

		$obj = (object) [
			'layout_id'     => $layoutId,
			'title'         => ($type === 'autosave') ? 'Autosave ' . $now : 'Manual ' . $now,
			'layout_data'   => $layoutJson,
			'revision_type' => in_array($type, ['manual', 'autosave'], true) ? $type : 'autosave',
			'created'       => $now,
			'created_by'    => (int) $user->id,
		];

		return $db->insertObject('#__flexicontent_pro_revisions', $obj);
	}

	// -------------------------------------------------------------------------
	// JTable
	// -------------------------------------------------------------------------

	/**
	 * @param string $type
	 * @param string $prefix
	 * @param array  $config
	 * @return \Joomla\CMS\Table\Table
	 */
	public function getTable($type = 'flexicontent_pro_layouts', $prefix = '', $config = [])
	{
		return Table::getInstance($type, '', $config);
	}
}
