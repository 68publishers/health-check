<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI;

use RuntimeException;
use Nette\Schema\Schema;
use Nette\Routing\Router;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Application\IPresenterFactory;
use Nette\DI\Definitions\ServiceDefinition;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\Application\HealthCheckRoute;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\Application\HealthCheckPresenter;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\Config\HealthCheckApplicationConfig;
use function count;
use function assert;
use function sprintf;

final class HealthCheckApplicationExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return HealthCheckApplicationConfig::getSchema();
	}

	public function loadConfiguration(): void
	{
		if (0 >= count($this->compiler->getExtensions(HealthCheckExtension::class))) {
			throw new RuntimeException(sprintf(
				'Please register the compiler extension of type %s.',
				HealthCheckExtension::class
			));
		}

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('presenter.healthCheck'))
			->setType(HealthCheckPresenter::class);
	}

	public function beforeCompile(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$presenterFactory = $builder->getDefinitionByType(IPresenterFactory::class);
		assert($config instanceof HealthCheckApplicationConfig && $presenterFactory instanceof ServiceDefinition);

		$presenterFactory->addSetup('setMapping', [
			[
				'HealthCheck' => ['SixtyEightPublishers\\HealthCheck\\Bridge\\Nette\\Application', '*', '*Presenter'],
			],
		]);

		if (false === $config->route) {
			return;
		}

		$router = $builder->getDefinitionByType(Router::class);
		assert($router instanceof ServiceDefinition);

		$router->addSetup('prepend', [
			'router' => new Statement(HealthCheckRoute::class, [
				$config->route,
			]),
		]);
	}
}
