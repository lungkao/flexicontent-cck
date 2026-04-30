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

require_once __DIR__ . '/flexicontent_basetable.php';

/**
 * Pro Layouts table — wraps #__flexicontent_pro_layouts
 */
#[AllowDynamicProperties]
class flexicontent_pro_layouts extends flexicontent_basetable
{
	/** @var int Primary key */
	public $id = null;

	/** @var string Layout title */
	public $title = '';

	/** @var int FLEXIcontent Type ID (0 = all types) */
	public $type_id = 0;

	/** @var int Category ID (0 = all categories) */
	public $catid = 0;

	/** @var string Assignment type: global|type|category|item|menu */
	public $assignment_type = 'global';

	/** @var string Assignment value (item/menu ID as string) */
	public $assignment_value = '';

	/** @var string|null JSON layout data */
	public $layout_data = null;

	/** @var int Theme ID (0 = none) */
	public $theme_id = 0;

	/** @var int Published state */
	public $state = 1;

	/** @var int Ordering */
	public $ordering = 0;

	/** @var string Note */
	public $note = '';

	/** @var string|null Created datetime */
	public $created = null;

	/** @var int Created by user ID */
	public $created_by = 0;

	/** @var string|null Modified datetime */
	public $modified = null;

	/** @var int Modified by user ID */
	public $modified_by = 0;

	/** @var int|null Checked out by user ID */
	public $checked_out = null;

	/** @var string|null Checked out time */
	public $checked_out_time = null;

	/**
	 * Constructor.
	 *
	 * @param \Joomla\Database\DatabaseDriver $db
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__flexicontent_pro_layouts', 'id', $db);
	}

	/**
	 * Validate and bind data before store.
	 *
	 * @return bool
	 */
	public function check(): bool
	{
		$this->title = trim($this->title);

		if (empty($this->title)) {
			$this->setError(\Joomla\CMS\Language\Text::_('FLEXI_PROTEMPLATE_ERROR_NO_TITLE'));
			return false;
		}

		$allowedAssignment = ['global', 'type', 'category', 'item', 'menu'];
		if (!in_array($this->assignment_type, $allowedAssignment, true)) {
			$this->assignment_type = 'global';
		}

		return true;
	}
}
