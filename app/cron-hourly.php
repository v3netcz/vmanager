<?php

use Nette\Diagnostics\Debugger;
use Nette\Environment;

// absolute filesystem path to the application root
define('APP_DIR', __DIR__);

// absolute filesystem path to the libraries
define('LIBS_DIR', APP_DIR . '/../libs');

// absolute filesystem path to the temporary files
define('TEMP_DIR', APP_DIR . '/../temp');

require LIBS_DIR . '/nette/Nette/loader.php';

// Always in production mode (logging errors)
Environment::setMode('production', true);
Debugger::enable();

Environment::loadConfig();
dibi::connect(Environment::getConfig('database'));

// + Schedule task initialization ================================================

// Put custom global tasks in here

// Modules should register their handlers in constructors
vManager\Application\ModuleManager::getModules();

// - Schedule task initialization ==============================================

vBuilder\Utils\Cron::run();