<?php

/**
 * This file is part of vBuilder Framework (vBuilder FW).
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vbuilder.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

/** THIS FILE IS CALLED AT BEGINING OF EVERY TEST *****************************/

// absolute filesystem path to test dir
define('TEST_DIR', __DIR__ . '/..');

// absolute filesystem path to the application root
define('APP_DIR', TEST_DIR . '/../app');

// absolute filesystem path to the libraries
define('LIBS_DIR', TEST_DIR . '/../libs');

// absolute filesystem path to the temporary files
define('TEMP_DIR', TEST_DIR . '/../temp');

require LIBS_DIR . '/vBuilderFw/tests/bootstrap.php';

/** PUT ANY APPLICATION SPECIFIC INITIALIZATION BELLOW ************************/

// Your initialization