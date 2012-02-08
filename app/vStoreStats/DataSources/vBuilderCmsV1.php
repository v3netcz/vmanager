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

namespace vManager\Modules\vStoreStats\DataSources;

use vManager, Nette,
	 vManager\Modules\System, vManager\Security\User, vBuilder\Orm\Behaviors\Secure;

/**
 * Data source for old vBuilder CMS
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 8, 2011
 */
class vBuilderCmsV1 extends BaseDataSource {

	const TABLE_ORDERS = 'shop2_orders';
	const TABLE_CARTS = 'shop2_carts';
	const TABLE_CART_ITEMS = 'shop2_cartItems';

	private $_since = -1;
	private $_until = -1;

	public function getSince() {
		if($this->_since === -1) $this->loadSinceUntil();
		return $this->_since;
	}
	
	public function getUntil() {
		if($this->_until === -1) $this->loadSinceUntil();
		return $this->_until;
	}
	
	private function loadSinceUntil() {
		$data = $this->connection->select('MIN([time]) AS [since], MAX([time]) AS [until]')
					->from(self::TABLE_ORDERS)
					->fetch(); 
		
		$this->_since = $data->since;
		$this->_until = $data->until;
	}

	public function getDailyOrders(\DateTime $since, \DateTime $until) {
		$ds = $this->connection->select('COUNT(*) AS [value], DATE_FORMAT([time], \'%Y-%m-%d\') AS [date]')->from(self::TABLE_ORDERS)
				// ->where('[state] = 1')
				->where('[time] >= %s', $since->format('Y-m-d'))
				->and('[time] <= %s', $until->format('Y-m-d 23:59:59'))
				->groupBy('[date]');
				
		return $ds->fetchAll();
	}

	public function getTotalRevenue(\DateTime $since, \DateTime $until) {
		return $this->connection->select('SUM(`totalCost`) AS [revenue]')
				->from(self::TABLE_ORDERS)->as('o')
				->join(self::TABLE_CARTS)->on('[order] = [o.id]')
				->where('[time] >= %s', $since->format('Y-m-d'))
				->and('[time] <= %s', $until->format('Y-m-d 23:59:59'))
				->fetchSingle();
	}

	public function getProductSellings(\DateTime $since, \DateTime $until) {
		$ds = $this->connection->select('[ci.id] AS [productId], [ci.name], SUM([amount] * [price]) AS [revenue], SUM([amount]) AS [amount]')
				->from(self::TABLE_ORDERS)->as('o')
				->join(self::TABLE_CARTS)->as('c')->on('[order] = [o.id]')
				->join(self::TABLE_CART_ITEMS)->as('ci')->on('[cart] = [c.id]')
				->where('[time] >= %s', $since->format('Y-m-d'))
				->and('[time] <= %s', $until->format('Y-m-d 23:59:59'))
				->groupBy('[ci.id]');
		
		return $ds->fetchAll();
	}

}