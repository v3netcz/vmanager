<?php

use Nette\Diagnostics\Debugger as Debug,
		Nette\Environment,
		Nette\Application\Routers\Route;

// Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR.'/nette/Nette/loader.php';
require APP_DIR.'/System/Configurator.php';

// Enable Nette\Debug for error visualisation & logging
Debug::$strictMode = TRUE;
Debug::enable();

$configurator = new vManager\Configurator;
Environment::setConfigurator($configurator);
$context = $configurator->container;

// Load configuration from config.neon file
Environment::loadConfig();

// Configure application
$application = $context->application;
$application->errorPresenter = 'System:Error';
$application->catchExceptions = Debug::$productionMode;

require LIBS_DIR . '/vBuilderFw/vBuilderFw/bootstrap.php';
require LIBS_DIR . '/NetteTranslator/shortcuts.php';

// Translator
$config = $context->config;
$lang = $config->get('system.language'); 
if($lang === null) $lang = $context->httpRequest->detectLanguage((array) Environment::getConfig('languages', array('en')));

Environment::setVariable('lang', $lang);
//NetteTranslator\Panel::register();

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

// Run the application!
$application->run();
