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

// absolute filesystem path to test dir
define('TEST_DIR', __DIR__ . '/..');

// absolute filesystem path to the application root
define('APP_DIR', TEST_DIR . '/../app');

// absolute filesystem path to the libraries
define('LIBS_DIR', TEST_DIR . '/../libs');

// absolute filesystem path to the temporary files
define('TEMP_DIR', TEST_DIR . '/../temp');

require LIBS_DIR . '/nette/tests/NetteTest/TestRunner.php';

echo "\n";
echo "vBuilder Framework Test script\n";
echo "------------------------------\n";
echo "This script is written for application testing and for testing of vBuilder Framework\n";
echo "For testing of Nette Framework or any other lib see it's own bundled tests\n";
echo "\n\n";

$runner = new TestRunner;

$testDirs = array(TEST_DIR . '/AppTests');
$args = new ArrayIterator(array_slice(isset($_SERVER['argv']) ? $_SERVER['argv'] : array(), 1));
foreach ($args as $arg) {
	if($arg == "all") $testDirs[] = LIBS_DIR . '/vBuilderFw/tests'; 
	elseif($arg == "fw") $testDirs = array(LIBS_DIR . '/vBuilderFw/tests');
	elseif($arg == "-p") {
		$args->next();
		$runner->phpBinary = $args->current();
	}
}

echo "All tests in '".implode($testDirs, "',\n             '")."' will be run\n\n";

foreach($testDirs as $dir) {
	$runner->path = $dir;
	$runner->run();
}


