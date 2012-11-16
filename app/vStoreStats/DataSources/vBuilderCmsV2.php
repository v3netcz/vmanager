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
	const TABLE_USERS = 'security_users';
	const TABLE_CUSTOMERS = 'shop_customers';
	const TABLE_SCHEDULED_DISCOUNTS = 'shop_scheduledDiscounts';

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
	
	public function getTotalOrdersFromNonRegisteredUsers(\DateTime $since, \DateTime $until) {
		$stats = $this->connection
				->select('COUNT(*) numOfOrders')
				->from(self::TABLE_ORDERS)->as('o')
				->where('[timestamp] >= %s', $since->format('Y-m-d'))
				->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
				->and('[user] IS NULL')
				->fetch();
	
		return intval($stats->numOfOrders);
	}
	
	public function getUniqueCustomers(\DateTime $since, \DateTime $until) {
		$stats = $this->connection
				->select('COUNT(DISTINCT [c.email]) numOfCustomers')
				->from(self::TABLE_ORDERS)->as('o')
				->join(self::TABLE_CUSTOMERS)->as('c')->on('[c.id] = [o.customer]')
				->where('[timestamp] >= %s', $since->format('Y-m-d'))
				->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
				->fetch();
	
		return intval($stats->numOfCustomers);
	}
	
	public function getNewCustomers(\DateTime $since, \DateTime $until) {
		// Pozor, data v tabulkce shop_customers nejsou normailizovana => duplicitni zaznamy!
		// TODO: opravit
	
		// Nerozlisuje, jestli neregistrovany zakaznik nakupoval vicekrat
		/* $stats = $this->connection
				->select('COUNT(DISTINCT [c.email]) numOfCustomers')
				->from(self::TABLE_ORDERS)->as('o')
				->leftJoin(self::TABLE_USERS)->as('u')->on('[o.user] = [u.id]')
				->join(self::TABLE_CUSTOMERS)->as('c')->on('[c.id] = [o.customer]')
				->where('[timestamp] >= %s', $since->format('Y-m-d'))
				->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
				->and('([registrationTime] >= %s OR [registrationTime] IS NULL)', $since->format('Y-m-d'))
				->fetch(); */
				
		// Neresi registraci uzivatelu (prvni objednavky prenesenych uzivatelu)
		// Pomale		
		/*$stats = $this->connection
				->select('COUNT(DISTINCT [c.email]) numOfCustomers')
				->from(self::TABLE_ORDERS)->as('o')
				->join(self::TABLE_CUSTOMERS)->as('c')->on('[c.id] = [o.customer]')
				->where('[timestamp] >= %s', $since->format('Y-m-d'))
				->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
				->and('[c.email] NOT IN (%SQL)',
					(string) $this->connection->select('[email]')->from(self::TABLE_ORDERS)->as('o2')
								->join(self::TABLE_CUSTOMERS)->as('c2')->on('[c2.id] = [o2.customer]')
								->where('[timestamp] < %s', $since->format('Y-m-d'))
				)
				->fetch();*/
				
		// Rozlistuje vicenasobne nakupy neregistrovanych zakazniku
		// Zohlednuje cas registrace uzivatele (kvuli odfiltrovani prenesenych uzivatelu ze stareho webu)
		// Optimalizace u registrovanych zakazniku
		// Pri registraci uzivatele se kontroluji e-mail na duplicity, takze jsou unikatni
		// (u neregistrovanych uzivatelu se unikatnost predpoklada)
		$stats = $this->connection
				->select('COUNT(DISTINCT [c.email]) numOfCustomers')
				->from(self::TABLE_ORDERS)->as('o')
				->leftJoin(self::TABLE_USERS)->as('u')->on('[o.user] = [u.id]')
				->join(self::TABLE_CUSTOMERS)->as('c')->on('[c.id] = [o.customer]')
				->where('[timestamp] >= %s', $since->format('Y-m-d'))
				->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
				->and('([registrationTime] >= %s OR ([registrationTime] IS NULL AND [c.email] NOT IN (%SQL) ))',
					$since->format('Y-m-d'),
					(string) $this->connection->select('[email]')->from(self::TABLE_ORDERS)->as('o2')
								->join(self::TABLE_CUSTOMERS)->as('c2')->on('[c2.id] = [o2.customer]')
								->where('[timestamp] < %s', $since->format('Y-m-d'))
				)
				->fetch();
	
		return intval($stats->numOfCustomers);
	}
	
	public function getTotalClasses(\DateTime $since, \DateTime $until) {
		$records = $this->connection
			->select('([totalClass] - 1) * 500 AS [min], [totalClass] * 500 AS [max], COUNT(*) AS [numOrders]')
			->from('(%sql)',
				(string) $this->connection->select('CEIL(SUM(oi.amount * oi.price) / 500) AS totalClass')
							->from(self::TABLE_ORDERS)->as('o')
							->join(self::TABLE_ORDER_ITEMS)->as('oi')->on('[o.id] = [oi.orderId]')
							->where('[timestamp] >= %s', $since->format('Y-m-d'))
							->and('[timestamp] <= %s', $until->format('Y-m-d 23:59:59'))
							->groupBy('[o.id]')
							->having('[totalClass] > 0')
			)->as('[tc]')
			 ->groupBy('[totalClass]')
			 ->orderBy('[totalClass]');
		
		return $records->fetchAll();
	}
	
	public function getActiveUserDiscounts(\DateTime $since, \DateTime $until) {
		$records = $this->connection
			->select("[id], [username], [name], [surname], MAX([percentageDiscount]) [percentageDiscount], MIN([until]) [until]")
			->from(self::TABLE_USERS)->as('u')
			->join(self::TABLE_SCHEDULED_DISCOUNTS)->as('sd')->on('[u.id] = [sd.user]')
			// ->where('[sd.until] >= %s', $since->format('Y-m-d'))
			->where('[sd.until] >= NOW()')
			->groupBy('[u.id]')
			->orderBy('[u.surname], [u.name]');
			
		return $records->fetchAll();
	}

}