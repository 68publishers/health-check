<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Exception;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\Exception\MultipleResultsException;

require __DIR__ . '/../bootstrap.php';

final class MultipleResultsExceptionTest extends TestCase
{
	public function testExceptionWithoutResults(): void
	{
		$exception = new MultipleResultsException([]);

		Assert::same([], $exception->getResults());
		Assert::same('Some health checks failed.', $exception->getMessage());
	}

	public function testExceptionWithResults(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('getName')
			->andReturn('first service');

		$firstServiceResult->shouldReceive('getError')
			->andReturn(new HealthCheckException('The first service failed.'));

		$secondServiceResult->shouldReceive('getName')
			->andReturn('second service');

		$secondServiceResult->shouldReceive('getError')
			->andReturn(new HealthCheckException('The second service failed.'));

		$exception = new MultipleResultsException([$firstServiceResult, $secondServiceResult]);

		Assert::same([$firstServiceResult, $secondServiceResult], $exception->getResults());
		Assert::same(
			"Some health checks failed:\n[first service]: The first service failed.\n[second service]: The second service failed.",
			$exception->getMessage()
		);
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

(new MultipleResultsExceptionTest())->run();
