<?php

// absolute filesystem path to the application root
if(!defined('APP_DIR')) define('APP_DIR', __DIR__ . '/../app');

// absolute filesystem path to the libraries
if(!defined('LIBS_DIR')) define('LIBS_DIR', __DIR__ . '/../libs');

// absolute filesystem path to the temporary files
if(!defined('TEMP_DIR')) define('TEMP_DIR', __DIR__ . '/../temp');

// absolute filesystem path to system static files
if(!defined('FILES_DIR')) define('FILES_DIR', __DIR__ . '/../files');

// absolute filesystem path to the web root
if(!defined('WWW_DIR')) define('WWW_DIR', __DIR__ . '/../www');

// absolute filesystem path to the application configuration
if(!defined('CONF_DIR')) define('CONF_DIR', APP_DIR . '/config');

// absolute filesystem path to the debugger logs
if(!defined('LOG_DIR')) define('LOG_DIR', __DIR__ . '/../log');

// --------------------

// Load Nette Framework
require LIBS_DIR . '/nette/Nette/loader.php';

// Configure application
$configurator = new Nette\Config\Configurator;

if(isset($_SERVER["DEVELOPMENT_MODE"]))
	$configurator->setProductionMode($_SERVER["DEVELOPMENT_MODE"] != true);

// Nette extensions
$configurator->onCompile[] = function($configurator, $compiler) {
	$compiler->addExtension('database', new DibiNetteExtension);
	
	// New vBuilder extension
	if(class_exists('vBuilder\Config\Extensions\vBuilderExtension'))
		$compiler->addExtension('vBuilder', new vBuilder\Config\Extensions\vBuilderExtension);
};

$configurator->setTempDirectory(TEMP_DIR);

$configurator->addParameters(array(
	'appDir' => APP_DIR,
	'libsDir' => LIBS_DIR,
	'tempDir' => TEMP_DIR,
	'logDir' => LOG_DIR,
	'confDir' => CONF_DIR,
	'filesDir' => FILES_DIR
));

// Enable RobotLoader - this will load all classes automatically
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();
	
// Create Dependency Injection container from config files
$configurator->addConfig(CONF_DIR . '/config.neon', false);
$container = $context = $configurator->createContainer();

// Enable Nette Debugger for error visualisation & logging
// It has to be after config load because of production mode detection (from config)
Nette\Diagnostics\Debugger::$strictMode = TRUE;
Nette\Diagnostics\Debugger::enable(FALSE);

// Compatibility layer for old DI service
$container->addService('connection', function($container) {
    return $container->database->connection;
});

// Load vManager modules
vManager\Application\ModuleManager::getModules();
