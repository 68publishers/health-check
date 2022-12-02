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
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::true($healthCheckResult->isOk());
	}

	public function testResultShouldNotBeOkIfAnyServiceResultIsNotOk(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::false($healthCheckResult->isOk());
	}

	public function testStatusShouldBeOkIfNoServiceResults(): void
	{
		$emptyHealthCheckResult = new HealthCheckResult();

		Assert::same('ok', $emptyHealthCheckResult->getStatus());
	}

	public function testStatusShouldBeOkIfAllServiceResultsAreOk(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::same('ok', $healthCheckResult->getStatus());
	}

	public function testResultShouldBeFailedIfAnyServiceResultIsNotOk(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::same('failed', $healthCheckResult->getStatus());
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
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::null($healthCheckResult->getError());
	}

	public function testErrorShouldBeReturnedIfAnyServiceCheckerIsNotOk(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

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
			'status' => 'ok',
			'is_ok' => TRUE,
		], $emptyHealthCheckResult->toArray());
	}

	public function testResultInFullExportModeShouldBeConvertedIntoArrayWithoutServiceCheckers(): void
	{
		$emptyHealthCheckResult = (new HealthCheckResult())->withExportMode(ExportMode::Full);

		Assert::same([
			'status' => 'ok',
			'is_ok' => TRUE,
			'services' => [],
		], $emptyHealthCheckResult->toArray());
	}

	public function testResultShouldBeConvertedIntoArrayWithServiceCheckers(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->times(2)
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->times(2)
			->andReturn(FALSE);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::same([
			'status' => 'failed',
			'is_ok' => FALSE,
		], $healthCheckResult->toArray());
	}

	public function testResultInFullExportModeShouldBeConvertedIntoArrayWithServiceCheckers(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->times(2)
			->andReturn(TRUE);

		$firstServiceResult->shouldReceive('toArray')
			->once()
			->andReturn(['name' => 'first service']);

		$secondServiceResult->shouldReceive('isOk')
			->times(2)
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('toArray')
			->once()
			->andReturn(['name' => 'second service']);

		$healthCheckResult = (new HealthCheckResult([$firstServiceResult, $secondServiceResult]))->withExportMode(ExportMode::Full);

		Assert::same([
			'status' => 'ok',
			'is_ok' => TRUE,
			'services' => [
				['name' => 'first service'],
				['name' => 'second service'],
			],
		], $healthCheckResult->toArray());
	}

	public function testResultShouldBeConvertedIntoJsonWithoutServiceCheckers(): void
	{
		$emptyHealthCheckResult = new HealthCheckResult();

		Assert::same('{"status":"ok","is_ok":true}', json_encode($emptyHealthCheckResult, JSON_THROW_ON_ERROR));
	}

	public function testResultInFullExportModeShouldBeConvertedIntoJsonWithoutServiceCheckers(): void
	{
		$emptyHealthCheckResult = (new HealthCheckResult())->withExportMode(ExportMode::Full);

		Assert::same('{"status":"ok","is_ok":true,"services":[]}', json_encode($emptyHealthCheckResult, JSON_THROW_ON_ERROR));
	}

	public function testResultShouldBeConvertedIntoJsonWithServiceCheckers(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->times(2)
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->times(2)
			->andReturn(FALSE);

		$healthCheckResult = new HealthCheckResult([$firstServiceResult, $secondServiceResult]);

		Assert::same('{"status":"failed","is_ok":false}', json_encode($healthCheckResult, JSON_THROW_ON_ERROR));
	}

	public function testResultInFullExportModeShouldBeConvertedIntoJsonWithServiceCheckers(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->times(2)
			->andReturn(TRUE);

		$firstServiceResult->shouldReceive('toArray')
			->once()
			->andReturn(['name' => 'first service']);

		$secondServiceResult->shouldReceive('isOk')
			->times(2)
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('toArray')
			->once()
			->andReturn(['name' => 'second service']);

		$healthCheckResult = (new HealthCheckResult([$firstServiceResult, $secondServiceResult]))->withExportMode(ExportMode::Full);

		Assert::same('{"status":"ok","is_ok":true,"services":[{"name":"first service"},{"name":"second service"}]}', json_encode($healthCheckResult, JSON_THROW_ON_ERROR));
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

(new HealthCheckResultTest())->run();
