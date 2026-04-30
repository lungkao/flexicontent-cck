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

/**
 * Pro Templates License Manager
 *
 * Stub implementation — all users are licensed in alpha.
 * Phase 2: validate key against remote server, cache result in session/db.
 */
class FlexicontentProLicenseManager
{
	/**
	 * Check whether the current site has a valid Pro license.
	 *
	 * @return bool  True if licensed (always true in stub).
	 */
	public static function isLicensed(): bool
	{
		// Phase 2: retrieve stored key from component params and validate remotely.
		// $key = \Joomla\CMS\Component\ComponentHelper::getParams('com_flexicontent')->get('pro_license_key', '');
		// return static::validateRemote($key);
		return true;
	}

	/**
	 * Return the stored license key (empty until Phase 2).
	 *
	 * @return string
	 */
	public static function getLicenseKey(): string
	{
		return \Joomla\CMS\Component\ComponentHelper::getParams('com_flexicontent')
			->get('pro_license_key', '');
	}

	/**
	 * Placeholder for remote key validation (Phase 2).
	 *
	 * @param  string  $key  License key to validate.
	 * @return bool
	 */
	protected static function validateRemote(string $key): bool
	{
		if (empty($key)) {
			return false;
		}
		// TODO Phase 2: HTTP request to https://flexicontent.org/api/license/validate
		return false;
	}
}
