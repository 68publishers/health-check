<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI;

use RuntimeException;
use Nette\DI\CompilerExtension;
use SixtyEightPublishers\HealthCheck\Bridge\Symfony\Console\Command\HealthCheckCommand;
use function count;
use function sprintf;

final class HealthCheckConsoleExtension extends CompilerExtension
{
	public function loadConfiguration(): void
	{
		if (0 >= count($this->compiler->getExtensions(HealthCheckExtension::class))) {
			throw new RuntimeException(sprintf(
				'Please register the compiler extension of type %s.',
				HealthCheckExtension::class
			));
		}

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('command.health_check'))
			->setType(HealthCheckCommand::class);
	}
}
