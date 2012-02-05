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

use vManager, vBuilder, Nette, vManager\Form, Gridito;

/**
 * Presenter for billing class balance
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 6, 2012
 */
class BalancePresenter extends vManager\Modules\System\SecuredPresenter {
	
	public function renderDefault() {
		$balance = $this->context->connection->query(
			'SELECT id, name, ' .
				'(SELECT SUM(IF(md = a.id, value, 0 - value)) FROM accounting_records WHERE md = a.id OR d = a.id) AS balance ' .
			'FROM accounting_billingClasses AS a ' .
			'HAVING balance IS NOT NULL'
		);
		
		$barData = array();
		foreach($balance as $curr) {
			$barData[$curr->id . '<br />' . wordwrap($curr->name, 30, '<br />')] = 
				$curr->balance > 0
					? $curr->balance
					: array('y' => $curr->balance, 'color' => '#a21d21');
		}
		
		$this->template->barData = $barData;
	}
	
}
