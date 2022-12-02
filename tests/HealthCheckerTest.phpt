<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\HealthChecker;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\ExportModeResolverInterface;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;
use function assert;

require __DIR__ . '/bootstrap.php';

final class HealthCheckerTest extends TestCase
{
	public function testExceptionShouldBeThrownIfDuplicatedServiceCheckerRegistered(): void
	{
		$serviceChecker1 = Mockery::mock(ServiceCheckerInterface::class);
		$serviceChecker2 = Mockery::mock(ServiceCheckerInterface::class);

		$serviceChecker1->shouldReceive('getName')
			->andReturn('test');

		$serviceChecker2->shouldReceive('getName')
			->andReturn('test');

		$checker = new HealthChecker();
		$checker->addServiceChecker($serviceChecker1);

		Assert::exception(
			static function () use ($checker, $serviceChecker2): void {
				$checker->addServiceChecker($serviceChecker2);
			},
			InvalidArgumentException::class,
			'Service checker with the name "test" is already registered.'
		);
	}

	public function testExceptionShouldBeThrownIfCheckedServiceNotRegistered(): void
	{
		$serviceChecker = Mockery::mock(ServiceCheckerInterface::class);

		$serviceChecker->shouldReceive('getName')
			->andReturn('test');

		$checker = new HealthChecker();
		$checker->addServiceChecker($serviceChecker);

		Assert::exception(
			static function () use ($checker): void {
				$checker->check(['missing']);
			},
			InvalidArgumentException::class,
			'Checker for the service named "missing" id not defined.'
		);
	}

	public function testResultShouldBeReturnedIfWithZeroServiceCheckersRegistered(): void
	{
		$checker = new HealthChecker();
		$healthCheckResult = $checker->check();

		Assert::type(HealthCheckResult::class, $healthCheckResult);
		assert($healthCheckResult instanceof HealthCheckResult);

		Assert::same([], $healthCheckResult->getResults());
	}

	public function testResultShouldBeReturnedWithServiceCheckersRegistered(): void
	{
		$serviceChecker1 = Mockery::mock(ServiceCheckerInterface::class);
		$serviceChecker2 = Mockery::mock(ServiceCheckerInterface::class);
		$result1 = Mockery::mock(ResultInterface::class);
		$result2 = Mockery::mock(ResultInterface::class);

		$serviceChecker1->shouldReceive('getName')
			->andReturn('test');

		$serviceChecker1->shouldReceive('check')
			->once()
			->andReturn($result1);

		$serviceChecker2->shouldReceive('getName')
			->andReturn('test2');

		$serviceChecker2->shouldReceive('check')
			->once()
			->andReturn($result2);

		$checker = new HealthChecker();
		$checker->addServiceChecker($serviceChecker1);
		$checker->addServiceChecker($serviceChecker2);

		$healthCheckResult = $checker->check();

		Assert::type(HealthCheckResult::class, $healthCheckResult);
		assert($healthCheckResult instanceof HealthCheckResult);

		Assert::same([$result1, $result2], $healthCheckResult->getResults());
	}

	public function testResultShouldBeReturnedIfServicesOnlyArgumentPassed(): void
	{
		$serviceChecker1 = Mockery::mock(ServiceCheckerInterface::class);
		$serviceChecker2 = Mockery::mock(ServiceCheckerInterface::class);
		$serviceChecker3 = Mockery::mock(ServiceCheckerInterface::class);
		$result1 = Mockery::mock(ResultInterface::class);
		$result3 = Mockery::mock(ResultInterface::class);

		$serviceChecker1->shouldReceive('getName')
			->andReturn('test');

		$serviceChecker1->shouldReceive('check')
			->once()
			->andReturn($result1);

		$serviceChecker2->shouldReceive('getName')
			->andReturn('test2');

		$serviceChecker3->shouldReceive('getName')
			->andReturn('test3');

		$serviceChecker3->shouldReceive('check')
			->once()
			->andReturn($result3);

		$checker = new HealthChecker();
		$checker->addServiceChecker($serviceChecker1);
		$checker->addServiceChecker($serviceChecker2);
		$checker->addServiceChecker($serviceChecker3);

		$healthCheckResult = $checker->check(['test', 'test3']);

		Assert::type(HealthCheckResult::class, $healthCheckResult);
		assert($healthCheckResult instanceof HealthCheckResult);

		Assert::same([$result1, $result3], $healthCheckResult->getResults());
	}

	public function testDefaultExportModeShouldBeSimple(): void
	{
		$checker = new HealthChecker();
		$healthCheckResult = $checker->check();

		Assert::type(HealthCheckResult::class, $healthCheckResult);
		assert($healthCheckResult instanceof HealthCheckResult);

		Assert::same(ExportMode::Simple, $healthCheckResult->getExportMode());
	}

	public function testCustomDefaultExportModeShouldBeUsed(): void
	{
		$exportModeResolver = Mockery::mock(ExportModeResolverInterface::class);

		$exportModeResolver->shouldNotReceive('resolve')
			->once()
			->andReturn(ExportMode::Full);

		$checker = new HealthChecker($exportModeResolver);
		$healthCheckResult = $checker->check();

		Assert::type(HealthCheckResult::class, $healthCheckResult);
		assert($healthCheckResult instanceof HealthCheckResult);

		Assert::same(ExportMode::Full, $healthCheckResult->getExportMode());
	}

	public function testDefaultExportModeShouldBeOverloadedByMethodArgument(): void
	{
		$checker = new HealthChecker();
		$healthCheckResult = $checker->check(NULL, ExportMode::Full);

		Assert::type(HealthCheckResult::class, $healthCheckResult);
		assert($healthCheckResult instanceof HealthCheckResult);

		Assert::same(ExportMode::Full, $healthCheckResult->getExportMode());
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

(new HealthCheckerTest())->run();
