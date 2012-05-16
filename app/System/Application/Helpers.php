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

		if($delta < 0) {
			$delta = round(abs($delta) / 60);

			if($delta == 0)
				return __('few seconds left');
			if($delta == 1)
				return __('in a minute');
			if($delta < 45)
				return _nx('in %d minute', 'in %d minutes', $delta, array($delta));
			if($delta < 90)
				return __('in one hour');
			if($delta < 1440)
				return _nx('in %d hour', 'in %d hours', round($delta / 60), array(round($delta / 60)));
			if($delta < 2880)
				return __('tomorrow');
			if($delta < 43200)
				return _nx('in %d day', 'in %d days', round($delta / 1440), array(round($delta / 1440)));
			if($delta < 86400)
				return __('in a month');
			if($delta < 525960)
				return _nx('in %d month', 'in %d months', round($delta / 43200), array(round($delta / 43200)));
			if($delta < 1051920)
				return __('in one year');

			return _nx('in %d year', 'in %d years', round($delta / 525960), array(round($delta / 525960)));
		}

		$delta = round($delta / 60);
		if($delta == 0)
			return __('few seconds ago');
		if($delta == 1)
			return __('1 minute ago');
		if($delta < 45)
			return _nx('%d minute ago', '%d minutes ago', $delta, array($delta));
		if($delta < 90)
			return __('about an hour ago');
		if($delta < 1440)
			return _nx('%d hour ago', '%d hours ago', round($delta / 60), array(round($delta / 60)));
		if($delta < 2880)
			return __('yesterday');
		if($delta < 43200)
			return _nx('%d day ago', '%d days ago', round($delta / 1440), array(round($delta / 1440)));
		if($delta < 86400)
			return __('a month ago');
		if($delta < 525960)
			return _nx('%d month ago', '%d months ago', round($delta / 43200), array(round($delta / 43200)));
		if($delta < 1051920)
			return __('a year ago');

		return _nx('%d year ago', '%d years ago', round($delta / 525960), array(round($delta / 525960)));
	}

	public static function timeDiffInWords($diffTimestamp) {
		$meta = floor($diffTimestamp/60);
		$result = '';
		if ($meta > 24*60) {
			$diff = floor($meta/(24*60));
			$result .= _nx('%d day, ', '%d days, ', $diff, array ($diff));
			$meta -= $diff*24*60;
		}
		if ($meta > 60) {
			$diff = floor($meta/60);
			$result .= _nx('%d hour ', '%d hours ', $diff, array ($diff));
			$meta -= $diff*60;
		}
		if ($meta > 1) {
			$result .= (isset($diff)?__('and '):'')._nx('%d minute', '%d minutes', $meta, array ($meta));
		}
		return $result;
	}
	
	public static function monthInWords($monthStr) {
		$y = mb_substr($monthStr, 0, 4);
		$m = intval(mb_substr($monthStr, 5, 2));
		
		switch($m) {
			case 1: return _x('January %d', array($y));
			case 2: return _x('February %d', array($y));
			case 3: return _x('March %d', array($y));
			case 4: return _x('April %d', array($y));
			case 5: return _x('May %d', array($y));
			case 6: return _x('June %d', array($y));
			case 7: return _x('July %d', array($y));
			case 8: return _x('August %d', array($y));
			case 9: return _x('September %d', array($y));
			case 10: return _x('October %d', array($y));
			case 11: return _x('November %d', array($y));
			case 12: return _x('December %d', array($y));
		}
		
		return $monthStr;
	}
	
	public static function dayOfWeekInWords(\DateTime $date) {

		switch($date->format('w')) {
			case 0: return __('Sunday');
			case 1: return __('Monday');
			case 2: return __('Tuesday');
			case 3: return __('Wednesday');
			case 4: return __('Thursday');
			case 5: return __('Friday');
			case 6: return __('Saturday');
		}
		
		return NULL;
	}
	
}
