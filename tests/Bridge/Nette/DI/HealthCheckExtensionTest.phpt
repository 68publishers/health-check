<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Bridge\Nette\DI;

use Closure;
use Tester\Assert;
use Tester\TestCase;
use Tester\CodeCoverage\Collector;
use Nette\DI\InvalidConfigurationException;
use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\HealthChecker;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;
use SixtyEightPublishers\HealthCheck\StaticExportModeResolver;
use SixtyEightPublishers\HealthCheck\Tests\Fixtures\HealthyServiceChecker;
use SixtyEightPublishers\HealthCheck\Tests\Fixtures\FullExportModeResolver;
use function assert;
use function call_user_func;

require __DIR__ . '/../../../bootstrap.php';

final class HealthCheckExtensionTest extends TestCase
{
	public function testExceptionShouldBeThrownIfInvalidExportModeConfigured(): void
	{
		Assert::exception(
			static function () {
				ContainerFactory::create(__DIR__ . '/config.error.InvalidExportMode.neon', true);
			},
			InvalidConfigurationException::class,
			"%A%Invalid export mode, allowed values are 'simple', 'full', 'full_if_debug', dynamic parameter or service reference.%A%"
		);
	}

	public function testMinimalConfigurationInProdMode(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.minimal.neon',
			false,
			StaticExportModeResolver::class,
			ExportMode::Simple,
			[]
		);
	}

	public function testMinimalConfigurationInDebugMode(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.minimal.neon',
			true,
			StaticExportModeResolver::class,
			ExportMode::Full,
			[]
		);
	}

	public function testConfigurationWithExportModeFromParameter(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.exportModeFromParameter.neon',
			true,
			StaticExportModeResolver::class,
			ExportMode::Simple,
			[]
		);
	}

	public function testConfigurationWithExportModeFromService(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.exportModeFromService.neon',
			true,
			FullExportModeResolver::class,
			ExportMode::Full,
			[]
		);
	}

	public function testConfigurationWithExportModeFromStatement(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.exportModeFromStatement.neon',
			true,
			FullExportModeResolver::class,
			ExportMode::Full,
			[]
		);
	}

	public function testConfigurationWithExportModeFull(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.exportModeFull.neon',
			true,
			StaticExportModeResolver::class,
			ExportMode::Full,
			[]
		);
	}

	public function testConfigurationWithExportModeFullIfDebugInProductionMode(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.exportModeFullIfDebug.neon',
			false,
			StaticExportModeResolver::class,
			ExportMode::Simple,
			[]
		);
	}

	public function testConfigurationWithExportModeFullIfDebugInDebugMode(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.exportModeFullIfDebug.neon',
			true,
			StaticExportModeResolver::class,
			ExportMode::Full,
			[]
		);
	}

	public function testConfigurationWithExportModeSimple(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.exportModeSimple.neon',
			true,
			StaticExportModeResolver::class,
			ExportMode::Simple,
			[]
		);
	}

	public function testConfigurationWithServiceCheckers(): void
	{
		$this->assertHealthCheckerService(
			__DIR__ . '/config.withServiceCheckers.neon',
			true,
			StaticExportModeResolver::class,
			ExportMode::Full,
			[
				'first' => new HealthyServiceChecker('first'),
				'second' => new HealthyServiceChecker('second'),
				'third' => new HealthyServiceChecker('third'),
			]
		);
	}

	private function assertHealthCheckerService(string $configFile, bool $debugMode, string $expectedExportModeResolverClassname, ExportMode $expectedExportMode, array $expectedServiceCheckers): void
	{
		$container = ContainerFactory::create($configFile, $debugMode);
		$checker = $container->getByType(HealthCheckerInterface::class);

		Assert::type(HealthChecker::class, $checker);
		assert($checker instanceof HealthChecker);

		call_user_func(Closure::bind(static function () use ($checker, $expectedExportModeResolverClassname, $expectedExportMode, $expectedServiceCheckers) {
			$resolver = $checker->exportModeResolver;

			Assert::type($expectedExportModeResolverClassname, $resolver);
			Assert::same($expectedExportMode, $resolver->resolve());
			Assert::equal($expectedServiceCheckers, $checker->serviceCheckers);
		}, null, HealthChecker::class));
	}

	protected function tearDown(): void
	{
		# save manually partial code coverage to free memory
		if (Collector::isStarted()) {
			Collector::save();
		}
	}
}

(new HealthCheckExtensionTest())->run();
