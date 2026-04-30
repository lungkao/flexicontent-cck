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
 * Pro Themes table — wraps #__flexicontent_pro_themes
 */
#[AllowDynamicProperties]
class flexicontent_pro_themes extends flexicontent_basetable
{
	/** @var int Primary key */
	public $id = null;

	/** @var string Theme title */
	public $title = '';

	/** @var string|null JSON theme data */
	public $theme_data = null;

	/** @var int Published state */
	public $state = 1;

	/** @var int Ordering */
	public $ordering = 0;

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
		parent::__construct('#__flexicontent_pro_themes', 'id', $db);
	}

	/**
	 * Validate data before store.
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

		return true;
	}
}
