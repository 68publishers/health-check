<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Cases\Result;

use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\Exception\MultipleResultsException;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

require __DIR__ . '/../../bootstrap.php';

class HealthCheckResultTestCase extends TestCase
{
	public function testResultWithoutServiceResults() : void
	{
		$result = new HealthCheckResult([], HealthCheckResult::ARRAY_EXPORT_MODEL_SIMPLE);

		Assert::same('health_check', $result->getName());
		Assert::same(TRUE, $result->isOk());
		Assert::same('ok', $result->getStatus());
		Assert::null($result->getError());

		$this->compareResultAgainstArray($result, [
			'status' => 'ok',
			'is_ok' => TRUE,
		]);

		# change array export mode
		$result->setArrayExportMode(HealthCheckResult::ARRAY_EXPORT_MODE_FULL);

		$this->compareResultAgainstArray($result, [
			'status' => 'ok',
			'is_ok' => TRUE,
			'services' => [],
		]);
	}

	public function testOkResult() : void
	{
		$result = new HealthCheckResult([
			new ServiceResult('dummy-1', TRUE, 'running', NULL),
			new ServiceResult('dummy-2', TRUE, 'running', NULL),
		], HealthCheckResult::ARRAY_EXPORT_MODEL_SIMPLE);

		Assert::same('health_check', $result->getName());
		Assert::same(TRUE, $result->isOk());
		Assert::same('ok', $result->getStatus());
		Assert::null($result->getError());

		$this->compareResultAgainstArray($result, [
			'status' => 'ok',
			'is_ok' => TRUE,
		]);

		# change array export mode
		$result->setArrayExportMode(HealthCheckResult::ARRAY_EXPORT_MODE_FULL);

		$this->compareResultAgainstArray($result, [
			'status' => 'ok',
			'is_ok' => TRUE,
			'services' => [
				[
					'name' => 'dummy-1',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
				[
					'name' => 'dummy-2',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
			],
		]);
	}

	public function testFailedResult() : void
	{
		$result = new HealthCheckResult([
			new ServiceResult('dummy-1', FALSE, 'down', new HealthCheckException('The service dummy-1 is down.')),
			new ServiceResult('dummy-2', TRUE, 'running', NULL),
			new ServiceResult('dummy-3', FALSE, 'down', new HealthCheckException('The service dummy-3 is down.')),
		], HealthCheckResult::ARRAY_EXPORT_MODEL_SIMPLE);

		Assert::same('health_check', $result->getName());
		Assert::same(FALSE, $result->isOk());
		Assert::same('failed', $result->getStatus());
		Assert::notNull($result->getError());

		Assert::exception(static function () use ($result) : void {
			throw $result->getError();
		}, MultipleResultsException::class, "Some health checks failed: \n[dummy-1]: The service dummy-1 is down.\n[dummy-3]: The service dummy-3 is down.");

		$this->compareResultAgainstArray($result, [
			'status' => 'failed',
			'is_ok' => FALSE,
		]);

		# change array export mode
		$result->setArrayExportMode(HealthCheckResult::ARRAY_EXPORT_MODE_FULL);

		$this->compareResultAgainstArray($result, [
			'status' => 'failed',
			'is_ok' => FALSE,
			'services' => [
				[
					'name' => 'dummy-1',
					'is_ok' => FALSE,
					'status' => 'down',
					'error' => 'The service dummy-1 is down.',
				],
				[
					'name' => 'dummy-2',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
				[
					'name' => 'dummy-3',
					'is_ok' => FALSE,
					'status' => 'down',
					'error' => 'The service dummy-3 is down.',
				],
			],
		]);
	}

	public function testServiceResultAddition() : void
	{
		$dummy1 = new ServiceResult('dummy-1', TRUE, 'running', NULL);
		$dummy2 = new ServiceResult('dummy-2', TRUE, 'running', NULL);
		$dummy3 = new ServiceResult('dummy-3', TRUE, 'running', NULL);

		$originalResult = new HealthCheckResult([$dummy1, $dummy2]);

		Assert::equal([$dummy1, $dummy2], $originalResult->getResults());

		$newResult = $originalResult->withResult($dummy3);

		Assert::notSame($originalResult, $newResult); # must be immutable

		Assert::equal([$dummy1, $dummy2], $originalResult->getResults());
		Assert::equal([$dummy1, $dummy2, $dummy3], $newResult->getResults());
	}

	private function compareResultAgainstArray(ResultInterface $result, array $array) : void
	{
		Assert::equal($array, $result->toArray());
		Assert::equal(json_encode($array, JSON_THROW_ON_ERROR), json_encode($result, JSON_THROW_ON_ERROR));
	}
}

(new HealthCheckResultTestCase())->run();
