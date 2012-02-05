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

use vBuilder;

/**
 * Billing class model
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 5, 2011
 * 
 * @Table(name="accounting_billingClasses")
 * 
 * @Column(id, pk)
 * @Column(name)
 * @Column(description)
 */
class BillingClass extends vBuilder\Orm\ActiveEntity {

}
