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

require_once __DIR__ . '/base/baselist.php';

/**
 * Pro Templates — list model
 */
#[AllowDynamicProperties]
class FlexicontentModelProtemplates extends FCModelAdminList
{
	/** @var string DB table */
	var $records_dbtbl  = 'flexicontent_pro_layouts';

	/** @var string JTable class name */
	var $records_jtable = 'flexicontent_pro_layouts';

	/** @var string State column */
	var $state_col = 'state';

	/** @var string Name column */
	var $name_col  = 'title';

	/** @var null No parent */
	var $parent_col = null;

	/** @var null No created_by filter */
	var $created_by_col = null;

	// Behaviour flags
	protected $listViaAccess = false;
	protected $copyRelations  = false;

	// Search columns shown in the search bar
	var $search_cols = [
		'FLEXI_TITLE'  => 'title',
		'FLEXI_NOTE'   => 'note',
	];

	var $default_order     = 'a.ordering';
	var $default_order_dir = 'ASC';

	var $hard_filters = [];

	/**
	 * Build the list query.
	 *
	 * @return \Joomla\Database\DatabaseQuery
	 */
	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query
			->select('a.*')
			->select('t.name AS type_name')
			->select('c.title AS cat_name')
			->from('#__flexicontent_pro_layouts AS a')
			->join('LEFT', '#__flexicontent_types AS t ON t.id = a.type_id')
			->join('LEFT', '#__categories AS c ON c.id = a.catid');

		// --- Filters ---

		// State
		$state = $this->getState('filter.state', '');
		if ($state !== '') {
			$query->where('a.state = ' . (int) $state);
		}

		// Type
		$type_id = (int) $this->getState('filter.type_id', 0);
		if ($type_id > 0) {
			$query->where('a.type_id = ' . $type_id);
		}

		// Search
		$search = $this->getState('filter.search', '');
		if ($search !== '') {
			$search = $db->quote('%' . $db->escape($search, true) . '%', false);
			$query->where('(a.title LIKE ' . $search . ' OR a.note LIKE ' . $search . ')');
		}

		// Ordering
		$orderCol = $this->getState('list.ordering',    $this->default_order);
		$orderDir = $this->getState('list.direction',   $this->default_order_dir);

		$allowedCols = ['a.ordering', 'a.title', 'a.state', 'a.type_id', 'a.catid', 'a.modified'];
		$orderCol = in_array($orderCol, $allowedCols, true) ? $orderCol : $this->default_order;
		$orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

		$query->order($orderCol . ' ' . $orderDir);

		return $query;
	}
}
