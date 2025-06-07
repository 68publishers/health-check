<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Bridge\Nette\DI;

use Tester\Assert;
use Tester\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use SixtyEightPublishers\HealthCheck\Bridge\Symfony\Console\Command\HealthCheckCommand;
use function assert;

require __DIR__ . '/../../../bootstrap.php';

final class HealthCheckConsoleExtensionTest extends TestCase
{
	public function testExceptionShouldBeThrownIfHealthCheckExtensionNotRegistered(): void
	{
		Assert::exception(
			static function () {
				ContainerFactory::create(__DIR__ . '/config.console.error.missingHealthCheckExtension.neon', true);
			},
			RuntimeException::class,
			"Please register the compiler extension of type SixtyEightPublishers\\HealthCheck\\Bridge\\Nette\\DI\\HealthCheckExtension."
		);
	}

	public function testExtensionShouldBeRegistered(): void
	{
		$container = ContainerFactory::create(__DIR__ . '/config.console.neon', true);
		$application = $container->getByType(Application::class);
		assert($application instanceof Application);

		Assert::type(HealthCheckCommand::class, $application->get('health-check'));
	}
}

(new HealthCheckConsoleExtensionTest())->run();
