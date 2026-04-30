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
 * Pro Themes — list model
 */
#[AllowDynamicProperties]
class FlexicontentModelProthemes extends FCModelAdminList
{
	var $records_dbtbl  = 'flexicontent_pro_themes';
	var $records_jtable = 'flexicontent_pro_themes';
	var $state_col      = 'state';
	var $name_col       = 'title';
	var $parent_col     = null;
	var $created_by_col = null;

	protected $listViaAccess = false;
	protected $copyRelations  = false;

	var $search_cols = [
		'FLEXI_TITLE' => 'title',
	];

	var $default_order     = 'a.ordering';
	var $default_order_dir = 'ASC';
	var $hard_filters      = [];

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('a.*')
			->from('#__flexicontent_pro_themes AS a');

		$state = $this->getState('filter.state', '');
		if ($state !== '') {
			$query->where('a.state = ' . (int) $state);
		}

		$search = $this->getState('filter.search', '');
		if ($search !== '') {
			$search = $db->quote('%' . $db->escape($search, true) . '%', false);
			$query->where('a.title LIKE ' . $search);
		}

		$orderCol = $this->getState('list.ordering',  $this->default_order);
		$orderDir = $this->getState('list.direction', $this->default_order_dir);

		$allowedCols = ['a.ordering', 'a.title', 'a.state', 'a.modified'];
		$orderCol    = in_array($orderCol, $allowedCols, true) ? $orderCol : $this->default_order;
		$orderDir    = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

		$query->order($orderCol . ' ' . $orderDir);

		return $query;
	}
}
