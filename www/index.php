<?php

// the identification of this site
define('SITE', '');

// absolute filesystem path to the web root
define('WWW_DIR', __DIR__);

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../app');

// absolute filesystem path to the configuration directory
define('CONF_DIR', APP_DIR . '/config');

// absolute filesystem path to static files directory
define('FILES_DIR', WWW_DIR . '/../files');

// absolute filesystem path to logging directory
define('LOG_DIR', WWW_DIR . '/../log');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/../libs');

// absolute filesystem path to the temporary files
define('TEMP_DIR', WWW_DIR . '/../temp');

// load bootstrap file
require APP_DIR . '/bootstrap.php';
