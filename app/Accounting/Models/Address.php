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
 * Address data class
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 31, 2011
 * 
 * @Table(name="accounting_addresses")
 * 
 * @Column(id, pk, type="integer", generatedValue)
 * @Column(street)
 * @Column(houseNumber)
 * @Column(city)
 * @Column(zip)
 * @Column(country)
 */
class Address extends vBuilder\Orm\ActiveEntity {

}
