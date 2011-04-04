<?php

/**
 * My Application bootstrap file.
 */


use Nette\Debug;
use Nette\Environment;
use Nette\Application\Route;


// Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR . '/nette/Nette/loader.php';


// Enable Nette\Debug for error visualisation & logging
Debug::$strictMode = TRUE;
Debug::enable(Debug::DEVELOPMENT);


// Load configuration from config.neon file
Environment::loadConfig();

// Dibi
dibi::connect(Environment::getConfig('database'));

// Configure application
$application = Environment::getApplication();
//$application->errorPresenter = 'Error';
//$application->catchExceptions = TRUE;

// Pri prechodu do produkcniho prostredi odkomentovat ty 2 predchozi radky
// a zakomentovat tuhle
$application->catchExceptions = false;


// Setup router
$application->onStartup[] = function() use ($application) {
	$router = $application->getRouter();

	$router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);

	$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
};


// Run the application!
$application->run();
