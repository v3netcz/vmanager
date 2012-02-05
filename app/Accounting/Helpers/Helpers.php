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

namespace vManager\Modules\Accounting;

use Nette;

/**
 * Latte template helpers
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 2, 2011
 */
class Helpers {

	public static function currency($value) {
		return str_replace(" ", "\xc2\xa0", number_format($value, 0, "", " "))."\xc2\xa0Kč";
	}
	
	public static function billingClass($class) {
		if(!($class instanceof BillingClass) || $class->id == '' || !$class->exists()) return '-';
	
		$str = mb_strlen($class->id) > 3 ? mb_substr($class->id, 0, 3) . "\xc2\xa0" . mb_substr($class->id, 3) : $class->id;
		$desc = '';
		
		if($class->name != '') $desc = $class->name;
		
		
		$el = Nette\Utils\Html::el("abbr")->setText($str);		
		if(!empty($desc)) $el->title($desc);
		return (String) $el;			
	}
	
	public static function evidenceId($id) {
		if(mb_strlen($id) == 7)
			return mb_substr($id, 0, 2)
					. "\xc2\xa0"
					. mb_substr($id, 2, 2)
					. "\xc2\xa0"
					. mb_substr($id, 4);
					
					
		return $id;		
	}

}