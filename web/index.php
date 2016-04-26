<?php
ini_set('session.cache_limiter','public');
session_cache_limiter(false);

// comment out the following two lines when deployed to production

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');
error_reporting(E_ALL ^ E_NOTICE);
(new yii\web\Application($config))->run();
