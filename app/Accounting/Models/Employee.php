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

use vManager,
	vBuilder,
	Nette;

/**
 * Employee data class
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 22, 2011
 * 
 * @Table(name="accounting_employees")
 * 
 * @Column(id, realName="employeeId", pk, type="integer", generatedValue)
 * @Column(in, type="string")
 * @Column(name, type="string")
 * @Column(surname, type="string")
 * @Column(email, type="string")
 * @Column(payPerHour, type="integer")
 */
class Employee extends vBuilder\Orm\ActiveEntity {

	private $_employedMonths;

	public function getDisplayName() {
		return $this->name . ' ' . $this->surname;
	}
	
	public function getEmployedMonths() {
		if(!isset($this->_employedMonths)) {
			$this->_employedMonths = array();
			
			$d = $this->context->connection->query(
				"SELECT DISTINCT DATE_FORMAT(`date`, '%Y-%m') AS `m` FROM [".WorkHour::getMetadata()->getTableName()."]"
			)->fetchAll();
			
			foreach($d as $curr) $this->_employedMonths[] = $curr->m;
		}
		
		return $this->_employedMonths;
	}
	
	public function getCurrentPay(\DateTime $date) {
		// Docasne reseno primo parametrem v evidenci
		// Pozdeji bude treba udelat datovane "smlouvy", protoze se plat muze menit
		
		return $this->payPerHour;
	}

}
