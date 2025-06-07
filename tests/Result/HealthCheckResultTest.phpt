<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Result;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\Exception\MultipleResultsException;
use function assert;

require __DIR__ . '/../bootstrap.php';

final class HealthCheckResultTest extends TestCase
{
	public function testNameShouldByReturned(): void
	{
		$healthCheckResult = new HealthCheckResult();

		Assert::same('health_check', $healthCheckResult->getName());
	}

	public function testExportModeShouldBeChangedAndResultsAreImmutable(): void
	{
		$healthCheckResult = new HealthCheckResult();
		$mutedHealthCheckResult = $healthCheckResult->withExportMode(ExportMode::Full);

		Assert::notSame($healthCheckResult, $mutedHealthCheckResult);
		Assert::same(ExportMode::Simple, $healthCheckResult->getExportMode());
		Assert::same(ExportMode::Full, $mutedHealthCheckResult->getExportMode());
	}

	public function testServiceResultShouldBeAddedAndResultsAreImmutable(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$emptyHealthCheckResult = new HealthCheckResult();
		$healthCheckResultWithFirstService = $emptyHealthCheckResult->withResult($firstServiceResult);
		$healthCheckResultWithSecondService = $emptyHealthCheckResult->withResult($secondServiceResult);
		$healthCheckResultWithFirstAndSecondService = $healthCheckResultWithFirstService->withResult($secondServiceResult);

		Assert::notSame($emptyHealthCheckResult, $healthCheckResultWithFirstService);
		Assert::notSame($emptyHealthCheckResult, $healthCheckResultWithSecondService);
		Assert::notSame($healthCheckResultWithSecondService, $healthCheckResultWithFirstAndSecondService);

		Assert::same([], $emptyHealthCheckResult->getResults());
		Assert::same([$firstServiceResult], $healthCheckResultWithFirstService->getResults());
		Assert::same([$secondServiceResult], $healthCheckResultWithSecondService->getResults());
		Assert::same([$firstServiceResult, $secondServiceResult], $healthCheckResultWithFirstAndSecondService->getResults());
	}

	public function testResultShouldBeOkIfNoServiceResults(): void
	{
		$emptyHealthCheckResult = new HealthCheckResult();

		Assert::true($emptyHealthCheckResult->isOk());
	}

	public function testResultShouldBeOkIfAllServiceResultsAreOk(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::true($healthCheckResult->isOk());
	}

	public function testResultShouldNotBeOkIfAnyServiceResultIsNotOk(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(false);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::false($healthCheckResult->isOk());
	}

	public function testDetailShouldContainEmptyServicesIfNoServicesDefined(): void
	{
		$emptyHealthCheckResult = new HealthCheckResult();

		Assert::equal(
			[
				'services' => [],
			],
			$emptyHealthCheckResult->getDetail(),
		);
	}

	public function testDetailShouldContainServicesIfDefined(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('toArray')
			->once()
			->andReturn([
				'name' => 'a',
				'is_ok' => true,
				'detail' => [],
				'error' => null,
			]);

		$secondServiceResult->shouldReceive('toArray')
			->once()
			->andReturn([
				'name' => 'b',
				'is_ok' => false,
				'detail' => ['status_code' => 503],
				'error' => '503 Service unavailable',
			]);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::equal([
			'services' => [
				[
					'name' => 'a',
					'is_ok' => true,
					'detail' => [],
					'error' => null,
				],
				[
					'name' => 'b',
					'is_ok' => false,
					'detail' => ['status_code' => 503],
					'error' => '503 Service unavailable',
				],
			],
		], $healthCheckResult->getDetail());
	}

	public function testErrorShouldBeNullWithoutServiceCheckers(): void
	{
		$emptyHealthCheckResult = new HealthCheckResult();

		Assert::null($emptyHealthCheckResult->getError());
	}

	public function testErrorShouldBeNullIfAllServiceCheckersAreOk(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::null($healthCheckResult->getError());
	}

	public function testErrorShouldBeReturnedIfAnyServiceCheckerIsNotOk(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(false);

		$secondServiceResult->shouldReceive('getName')
			->andReturn('secondService');

		$secondServiceResult->shouldReceive('getError')
			->andReturn(new HealthCheckException('The second service failed.'));

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);
		$error = $healthCheckResult->getError();

		Assert::type(MultipleResultsException::class, $error);
		assert($error instanceof MultipleResultsException);
		Assert::same([$secondServiceResult], $error->getResults());
	}

	public function testResultShouldBeConvertedIntoArrayWithoutServiceCheckers(): void
	{
		$emptyHealthCheckResult = new HealthCheckResult();

		Assert::same([
			'is_ok' => true,
		], $emptyHealthCheckResult->toArray());
	}

	public function testResultInFullExportModeShouldBeConvertedIntoArrayWithoutServiceCheckers(): void
	{
		$emptyHealthCheckResult = (new HealthCheckResult())->withExportMode(ExportMode::Full);

		Assert::same([
			'is_ok' => true,
			'detail' => [
				'services' => [],
			],
		], $emptyHealthCheckResult->toArray());
	}

	public function testResultShouldBeConvertedIntoArrayWithServiceCheckers(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(false);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::same([
			'is_ok' => false,
		], $healthCheckResult->toArray());
	}

	public function testResultInFullExportModeShouldBeConvertedIntoArrayWithServiceCheckers(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$firstServiceResult->shouldReceive('toArray')
			->once()
			->andReturn([
				'name' => 'a',
				'is_ok' => true,
				'detail' => [],
				'error' => null,
			]);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$secondServiceResult->shouldReceive('toArray')
			->once()
			->andReturn([
				'name' => 'b',
				'is_ok' => true,
				'detail' => ['status_code' => 200],
				'error' => null,
			]);

		$healthCheckResult = (new HealthCheckResult([$firstServiceResult, $secondServiceResult]))->withExportMode(ExportMode::Full);

		Assert::same([
			'is_ok' => true,
			'detail' => [
				'services' => [
					[
						'name' => 'a',
						'is_ok' => true,
						'detail' => [],
						'error' => null,
					],
					[
						'name' => 'b',
						'is_ok' => true,
						'detail' => ['status_code' => 200],
						'error' => null,
					],
				],
			],
		], $healthCheckResult->toArray());
	}

	public function testResultShouldBeConvertedIntoJsonWithoutServiceCheckers(): void
	{
		$emptyHealthCheckResult = new HealthCheckResult();

		Assert::same('{"is_ok":true}', json_encode($emptyHealthCheckResult, JSON_THROW_ON_ERROR));
	}

	public function testResultInFullExportModeShouldBeConvertedIntoJsonWithoutServiceCheckers(): void
	{
		$emptyHealthCheckResult = (new HealthCheckResult())->withExportMode(ExportMode::Full);

		Assert::same('{"is_ok":true,"detail":{"services":[]}}', json_encode($emptyHealthCheckResult, JSON_THROW_ON_ERROR));
	}

	public function testResultShouldBeConvertedIntoJsonWithServiceCheckers(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(false);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::same('{"is_ok":false}', json_encode($healthCheckResult, JSON_THROW_ON_ERROR));
	}

	public function testResultInFullExportModeShouldBeConvertedIntoJsonWithServiceCheckers(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$firstServiceResult->shouldReceive('jsonSerialize')
			->once()
			->andReturn([
				'name' => 'a',
				'is_ok' => true,
				'detail' => (object) [],
				'error' => null,
			]);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(true);

		$secondServiceResult->shouldReceive('jsonSerialize')
			->once()
			->andReturn([
				'name' => 'b',
				'is_ok' => true,
				'detail' => ['status_code' => 200],
				'error' => null,
			]);

		$healthCheckResult = (new HealthCheckResult([$firstServiceResult, $secondServiceResult]))->withExportMode(ExportMode::Full);

		Assert::same('{"is_ok":true,"detail":{"services":[{"name":"a","is_ok":true,"detail":{},"error":null},{"name":"b","is_ok":true,"detail":{"status_code":200},"error":null}]}}', json_encode($healthCheckResult, JSON_THROW_ON_ERROR));
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

(new HealthCheckResultTest())->run();
