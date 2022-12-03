<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Bridge\Nette\DI;

use Tester\Helpers;
use Nette\DI\Container;
use Nette\Bootstrap\Configurator;
use function uniqid;
use function dirname;
use function debug_backtrace;
use function sys_get_temp_dir;

final class ContainerFactory
{
	private function __construct()
	{
	}

	public static function create(string|array $configFiles, bool $debugMode): Container
	{
		$tempDir = sys_get_temp_dir() . '/' . uniqid('68publishers:HealthCheck', true);
		$backtrace = debug_backtrace();

		Helpers::purge($tempDir);

		$configurator = new Configurator();
		$configurator->setTempDirectory($tempDir);
		$configurator->setDebugMode($debugMode);

		$configurator->addParameters([
			'cwd' => dirname($backtrace[0]['file']),
		]);

		foreach ((array) $configFiles as $configFile) {
			$configurator->addConfig($configFile);
		}

		return $configurator->createContainer();
	}
}
