<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Use absolute paths
$rootDir = dirname(dirname(__DIR__));
require($rootDir . '/vendor/autoload.php');
require($rootDir . '/vendor/yiisoft/yii2/Yii.php');

// Don't run the application, just load the config
$config = require(__DIR__ . '/config/web.php');
