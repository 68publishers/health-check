<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Omni\DI;

use Nette\Schema\Schema;
use Apitte\Core\DI\ApiExtension;
use Symfony\Component\Console\Command\Command;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\Config\HealthCheckConfig;
use SixtyEightPublishers\ExtensionBundle\Bridge\Contracts\AbstractCompilerExtension;
use SixtyEightPublishers\HealthCheck\Bridge\Symfony\Console\Command\HealthCheckCommand;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\Configurator\HealthCheckExtensionConfigurator;
use SixtyEightPublishers\HealthCheck\Bridge\Omni\Application\Apitte\Controller\HealthCheckController;
use function assert;
use function class_exists;

final class HealthCheckExtension extends AbstractCompilerExtension
{
	private HealthCheckExtensionConfigurator $configurator;

	public static function getDefaultConfiguration(): array
	{
		return [
			'service_checkers' => [],
			'export_mode' => HealthCheckConfig::ExportModeFullIfDebug,
		];
	}

	protected function getConfigSchemaWhenEnabled(): Schema
	{
		return HealthCheckConfig::getSchema();
	}

	protected function loadConfigurationWhenEnabled(): void
	{
		$config = $this->getConfig();
		assert($config instanceof HealthCheckConfig);

		$this->configurator = new HealthCheckExtensionConfigurator(
			extension: $this,
			compiler: $this->compiler,
			config: $config,
		);

		$this->configurator->loadConfiguration();
		$this->loadConfigurationApitte();
		$this->loadConfigurationConsole();
	}

	protected function beforeCompileWhenEnabled(): void
	{
		$this->configurator->beforeCompile();
	}

	private function loadConfigurationApitte(): void
	{
		if ([] === $this->compiler->getExtensions(ApiExtension::class)) {
			return;
		}

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('bridge.apitte.controller.healthCheck'))
			->setType(HealthCheckController::class);
	}

	private function loadConfigurationConsole(): void
	{
		if (class_exists(Command::class)) {
			$builder = $this->getContainerBuilder();

			$builder->addDefinition($this->prefix('bridge.console.command.healthCheck'))
				->setType(HealthCheckCommand::class);
		}
	}
}
