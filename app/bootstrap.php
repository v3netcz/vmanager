<?php

use Nette\Application\Routers\Route;

// Load Nette Framework
require LIBS_DIR . '/nette/Nette/loader.php';

// Configure application
$configurator = new Nette\Config\Configurator;

// Nette extensions
$configurator->onCompile[] = function($configurator, $compiler) {
	$compiler->addExtension('database', new DibiNetteExtension);
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
Nette\Diagnostics\Debugger::enable($container->parameters['productionMode']);

// Configure application
$application = $container->application;
$application->errorPresenter = 'System:Error';
$application->catchExceptions = $container->parameters['productionMode'];

require LIBS_DIR . '/vBuilderFw/vBuilderFw/loader.php';

// Load vManager modules
vManager\Application\ModuleManager::getModules();

// Captcha (https://github.com/PavelMaca/CaptchaControl)
PavelMaca\Captcha\CaptchaControl::register();

// Setup router
$application->onStartup[] = array('vManager\Modules\System\FilesPresenter', 'setupRoutes');
$application->onStartup[] = function() use ($application) {
	$router = $application->getRouter();	
	
	$router[] = new Route('index.php', 'System:Homepage:default', Route::ONE_WAY);

	$router[] = new Route('<presenter>/<action>[/<id>]', 'System:Homepage:default');
};

// Configure and run the application!
$container->application->run();