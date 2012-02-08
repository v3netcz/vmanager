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
 * Data source for new vBuilder CMS (Nette based)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 8, 2011
 */
class vBuilderCmsV2 extends BaseDataSource {

	const TABLE_ORDERS = 'shop_orders';
	const TABLE_ORDER_ITEMS = 'shop_orderItems';

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
		$data = $this->connection->select('MIN([timestamp]) AS [since], MAX([timestamp]) AS [until]')
					->from(self::TABLE_ORDERS)
					->fetch();
		
		$this->_since = $data->since;
		$this->_until = $data->until;
	}

	public function getDailyOrders(\DateTime $since, \DateTime $until) {
		$ds = $this->connection->select('COUNT(*) AS [value], DATE_FORMAT([timestamp], \'%Y-%m-%d\') AS [date]')
				->from(self::TABLE_ORDERS)
				// ->where('[state] = 1')
				->where('[timestamp] >= %s', $since->format('Y-m-d'))
				->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
				->groupBy('[date]');
				
		return $ds->fetchAll();
	}

	public function getTotalRevenue(\DateTime $since, \DateTime $until) {
		$stats = $this->connection
				->select('SUM([amount]*[price]) revenue')
				->from(self::TABLE_ORDER_ITEMS)->as('oi')
				->join(self::TABLE_ORDERS)->as('o')->on('[o.id] = [oi.orderId]')
				->where('[timestamp] >= %s', $since->format('Y-m-d'))
				->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
				->and('[productId] > 0')
				->fetch();
				
		return floatval($stats->revenue);
	}

	public function getProductSellings(\DateTime $since, \DateTime $until) {
		$ds = $this->connection
				->select('SUM([amount]) amount, SUM([amount]*[price]) revenue, [productId], [name]')
				->from(self::TABLE_ORDER_ITEMS)->as('oi')
				->join(self::TABLE_ORDERS)->as('o')->on('[o.id] = [oi.orderId]')
				->where('[timestamp] >= %s', $since->format('Y-m-d'))
				->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
				->and('[productId] > 0')
				->groupBy('[productId]');
		
		return $ds->fetchAll();
	}

}