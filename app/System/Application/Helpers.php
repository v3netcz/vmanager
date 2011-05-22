<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vManager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vManager. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vManager\Application;

/**
 * Latte filter helpers
 *
 * @author Adam Staněk (velbloud)
 * @since May 13, 2011
 */
class Helpers {

	/**
	 * Helper for getting time in words.
	 * Based on: http://addons.nette.org/cs/helper-time-ago-in-words by
	 * David Grudl
	 * 
	 * @param DateTime|int|string $time
	 * @return string formatted time 
	 */
	public static function timeAgoInWords($time) {
		if(!$time) {
			return FALSE;
		} elseif(is_numeric($time)) {
			$time = (int) $time;
		} elseif($time instanceof \DateTime) {
			$time = $time->format('U');
		} else {
			$time = strtotime($time);
		}

		$delta = time() - $time;
		
		$delta = round($delta / 60);
		if($delta == 0)
			return __('few seconds ago');
		if($delta == 1)
			return __('1 minute ago');
		if($delta < 45)
			return _x('%d minutes ago', array($delta));
		if($delta < 90)
			return __('about an hour ago');
		if($delta < 1440)
			return _x('about %d hours ago', array(round($delta / 60)));
		if($delta < 2880)
			return __('yesterday');
		if($delta < 43200)
			return _x('%d days ago', array(round($delta / 1440)));
		if($delta < 86400)
			return __('a month ago');
		if($delta < 525960)
			return _x('%d months ago', array(round($delta / 43200)));
		if($delta < 1051920)
			return __('a year ago');
		
		return _x('%d years ago', array(round($delta / 525960)));		
	}

}