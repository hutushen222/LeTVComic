<?php
define('ROOT', __DIR__); // No slash

require ROOT . '/vendor/autoload.php';
require ROOT . '/functions.php';

$config = require ROOT . '/config.php';

set_error_handler('letvErrorHandler');

// 初始化Idiorm
ORM::configure($config['database']['default'], null);
ORM::raw_execute('SET NAMES UTF8', NULL);
ORM::configure('logging', true);

// 初始化SHD
use ThauEx\SimpleHtmlDom\SHD;
SHD::$fileCacheDir = ROOT . '/storage/cache';

