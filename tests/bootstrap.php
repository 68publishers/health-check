<?php

declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Please run `composer install`';
	exit(1);
}

Tester\Environment::setup();

if (!defined('TEMP_PATH')) {
	define('TEMP_PATH', __DIR__ . '/temp');
}
