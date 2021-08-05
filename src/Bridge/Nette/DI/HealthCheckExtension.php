<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use SixtyEightPublishers\HealthCheck\HealthChecker;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;

final class HealthCheckExtension extends CompilerExtension
{
	/**
	 * {@inheritDoc}
	 */
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'service_checkers' => Expect::listOf('string|' . Statement::class)
				->default([])
				->before(static function (array $items) {
					return array_map(static function ($item) {
						return $item instanceof Statement ? $item : new Statement($item);
					}, $items);
				}),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$healthChecker = $builder->addDefinition($this->prefix('health_checker'))
			->setType(HealthCheckerInterface::class)
			->setFactory(HealthChecker::class);

		foreach ($this->config->service_checkers as $i => $serviceCheckerFactory) {
			$serviceChecker = $builder->addDefinition($this->prefix('service_checker.' . $i))
				->setAutowired(FALSE)
				->setType(ServiceCheckerInterface::class)
				->setFactory($serviceCheckerFactory);

			$healthChecker->addSetup('addServiceChecker', [$serviceChecker]);
		}
	}
}
