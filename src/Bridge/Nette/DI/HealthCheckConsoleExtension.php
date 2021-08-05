<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI;

use Nette\DI\CompilerExtension;
use SixtyEightPublishers\HealthCheck\Exception\RuntimeException;
use SixtyEightPublishers\HealthCheck\Bridge\Symfony\Console\HealthCheckCommand;

final class HealthCheckConsoleExtension extends CompilerExtension
{
	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		if (0 >= count($this->compiler->getExtensions(HealthCheckExtension::class))) {
			throw new RuntimeException(sprintf(
				'The extension %s can be used only with %s.',
				static::class,
				HealthCheckExtension::class
			));
		}

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('command.health_check'))
			->setType(HealthCheckCommand::class);
	}
}
