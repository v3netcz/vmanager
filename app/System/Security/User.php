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

namespace vManager\Security;

use vManager, vBuilder, Nette;

/**
 * Overloaded user entity
 *
 * @Table(name="security_users")
 *
 * @Behavior(Secure)
 * 
 * @Column(id, pk, type="integer", generatedValue)
 * @Column(username, type="string")
 * @Column(password, type="string")
 * @Column(email, type="string")
 * @Column(registrationTime, type="DateTime")
 * @Column(roles, type="OneToMany", table="security_user_roles", joinOn="id=user")
 * 
 * @author Adam Staněk (V3lbloud)
 * @since Apr 28, 2011
 */
class User extends vBuilder\Security\User {
	
	/**
	 * Returns avatar picture URL
	 * 
	 * @param bool true if URL should be absolute URI (starts with http://)
	 * @return string URL
	 */
	function getAvatarUrl($absolute = false) {
		// TODO: dynamicke na zaklde upravy v profilu
		
		return vManager\Modules\System::getBasePath($absolute)
				  . '/images/profile.jpg';
	}
	
}
