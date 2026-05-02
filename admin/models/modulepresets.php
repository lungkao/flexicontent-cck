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

require_once __DIR__ . '/base/baselist.php';

/**
 * Module Presets — list model
 */
#[AllowDynamicProperties]
class FlexicontentModelModulepresets extends FCModelAdminList
{
	var $records_dbtbl  = 'flexicontent_module_presets';
	var $records_jtable = 'flexicontent_module_presets';
	var $state_col      = 'state';
	var $name_col       = 'title';
	var $parent_col     = null;
	var $created_by_col = null;

	protected $listViaAccess = false;
	protected $copyRelations  = false;

	var $search_cols = [
		'FLEXI_TITLE' => 'title',
	];

	var $default_order     = 'p.ordering';
	var $default_order_dir = 'ASC';
	var $hard_filters      = [];

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('p.id, p.title, p.layout, p.tags, p.description, p.state, p.ordering')
			->from('#__flexicontent_module_presets AS p');

		// State filter
		$state = $this->getState('filter.state', '');
		if ($state !== '') {
			$query->where('p.state = ' . (int) $state);
		}

		// Layout filter
		$layout = $this->getState('filter.layout', '');
		if ($layout !== '') {
			$query->where('p.layout = ' . $db->quote($layout));
		}

		// Search filter (title or description)
		$search = $this->getState('filter.search', '');
		if ($search !== '') {
			$escaped = $db->quote('%' . $db->escape($search, true) . '%', false);
			$query->where(
				'(p.title LIKE ' . $escaped .
				' OR p.description LIKE ' . $escaped . ')'
			);
		}

		// Ordering
		$orderCol = $this->getState('list.ordering',  $this->default_order);
		$orderDir = $this->getState('list.direction', $this->default_order_dir);

		$allowedCols = ['p.ordering', 'p.title', 'p.layout', 'p.state', 'p.id'];
		$orderCol    = in_array($orderCol, $allowedCols, true) ? $orderCol : $this->default_order;
		$orderDir    = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

		$query->order($orderCol . ' ' . $orderDir . ', p.title ASC');

		return $query;
	}
}
