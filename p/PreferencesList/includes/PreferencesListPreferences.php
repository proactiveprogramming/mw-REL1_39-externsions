<?php
/*
 * Copyright (C) 2017 Ike Hecht <tosfos@yahoo.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Description of PreferencesListPreferences
 *
 * @author Ike Hecht <tosfos@yahoo.com>
 */
class PreferencesListPreferences {

	/**
	 * Gets the desired Preferences for the user requested.
	 *
	 * @param User $user
	 * @param array $desiredPref
	 * @param IContextSource $context
	 * @param int $format
	 *
	 * @return array|null
	 * @throws MWException
	 */
	public static function getPreferences(
		$user,
		array $desiredPref,
		IContextSource $context,
		$format = PreferencesList::TABLE
	) {
		$defaultPreferences = PreferencesListUtils::getPreferences( $user, $context, $format );

		// Discard the unneeded preference fields
		$filteredPreferences = array_filter(
			$defaultPreferences,
			static function ( $key ) use ( $desiredPref ) {
				return in_array( $key, $desiredPref );
			},
			ARRAY_FILTER_USE_KEY
		);

		unset( $defaultPreferences );

		// PHP > 5.6
		return $filteredPreferences;
	}

}
