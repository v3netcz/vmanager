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


namespace vManager;

use vBuilder,
		Nette;

require_once LIBS_DIR . '/vBuilderFw/vBuilderFw/Configurator.php';

/**
 * Configurator for vManager
 *
 * @author Adam Staněk (V3lbloud)
 * @since Oct 5, 2011
 */
class Configurator extends vBuilder\Configurator {
	
	/**
	 * @return Nette\Application\IPresenterFactory
	 */
	public static function createServicePresenterFactory(Nette\DI\Container $container) {
		return new Application\PresenterFactory(
				  isset($container->params['appDir']) ? $container->params['appDir'] : NULL,
				  $container
		);
	}
	
}
