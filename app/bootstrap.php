<?php

/**
 * My Application bootstrap file.
 */


use Nette\Diagnostics\Debugger;
use Nette\Environment;
use Nette\Application\Routers\Route;


// Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR . '/nette/Nette/loader.php';
require LIBS_DIR . '/NetteTranslator/shortcuts.php';

// Enables debuging mode
// !!! Comment line bellow when going to production environment !!!
//Environment::setMode('production', false);

// Enable Nette\Debug for error visualisation & logging
Debugger::$strictMode = TRUE;
Debugger::enable();

// Load configuration from config.neon file
Environment::loadConfig();

// Dibi
dibi::connect(Environment::getConfig('database'));

// Captcha (https://github.com/PavelMaca/CaptchaControl)
PavelMaca\Captcha\CaptchaControl::register();

// Load vManager modules
vManager\Application\ModuleManager::getModules();

// Translator
// TODO: Chtelo by to ukladat v nejakych uzivatelskych settings a detekci az jako fallback
Environment::setVariable('lang', Environment::getHttpRequest()->detectLanguage((array) Environment::getConfig('languages', array('en'))));
//NetteTranslator\Panel::register();

// Configure application
$application = Environment::getApplication();
$application->errorPresenter = 'System:Error';
$application->catchExceptions = Environment::isProduction();


// Setup router
$application->onStartup[] = function() use ($application) {
	$router = $application->getRouter();

	$router[] = new Route('index.php', 'System:Homepage:default', Route::ONE_WAY);

	$router[] = new Route('<presenter>/<action>[/<id>]', 'System:Homepage:default');
};

// Run the application!
$application->run();
