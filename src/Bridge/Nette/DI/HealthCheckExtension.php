<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI;

use Nette\Schema\Schema;
use Nette\DI\CompilerExtension;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\Config\HealthCheckConfig;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\Configurator\HealthCheckExtensionConfigurator;
use function assert;

final class HealthCheckExtension extends CompilerExtension
{
	private HealthCheckExtensionConfigurator $configurator;

	public function getConfigSchema(): Schema
	{
		return HealthCheckConfig::getSchema();
	}

	public function loadConfiguration(): void
	{
		$config = $this->getConfig();
		assert($config instanceof HealthCheckConfig);

		$this->configurator = new HealthCheckExtensionConfigurator(
			extension: $this,
			compiler: $this->compiler,
			config: $config,
		);

		$this->configurator->loadConfiguration();
	}

	public function beforeCompile(): void
	{
		$this->configurator->beforeCompile();
	}
}
