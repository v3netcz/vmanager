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
 * Interface for implementing modules showable in main menu without
 * system template modification
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
interface IMenuEnabledModule {
	
	/**
	 * Returns menu structure for this module
	 * 
	 * Sample structure:
	 * <code>
	 *		$menu = array(
	 *			array(
	 *				'url' => 'http://some.url',
	 *				'label' => 'My menu item',
	 *				'children' => array(
	 *					array(
	 *						'url' => 'http://someother.url',
	 *						'label' => 'First subitem'
	 *					)
	 *				))
	 *		);
	 * </code>
	 * 
	 * @return array
	 */
	public function getMenuItems();
	
}

