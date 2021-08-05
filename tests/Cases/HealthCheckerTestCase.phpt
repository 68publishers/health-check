<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Cases;

use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\HealthChecker;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\Exception\MultipleResultsException;
use SixtyEightPublishers\HealthCheck\Exception\InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;
use SixtyEightPublishers\HealthCheck\Tests\Fixture\ServiceChecker\DownServiceChecker;
use SixtyEightPublishers\HealthCheck\Tests\Fixture\ServiceChecker\RunningServiceChecker;

require __DIR__ . '/../bootstrap.php';

class HealthCheckerTestCase extends TestCase
{
	public function testExceptionShouldBeThrownOnDuplicateServiceChecker() : void
	{
		$checker = new HealthChecker();

		$checker->addServiceChecker(new RunningServiceChecker('dummy-1'));

		Assert::exception(static function () use ($checker) {
			$checker->addServiceChecker(new RunningServiceChecker('dummy-1'));
		}, InvalidArgumentException::class, 'The service checker with name "dummy-1" is already registered.');
	}

	public function testExceptionShouldBeThrownWhenRequestedServiceCheckerNotDefined() : void
	{
		$checker = new HealthChecker();

		$checker->addServiceChecker(new RunningServiceChecker('dummy-1'));

		Assert::exception(static function () use ($checker) {
			$checker->check(['dummy-2']);
		}, InvalidArgumentException::class, 'Checker for the service named "dummy-2" id not defined.');
	}

	public function testCheckerShouldReturnOkResult() : void
	{
		$checker = new HealthChecker();

		$testCommon = static function (ResultInterface $result) {
			Assert::same('health_check', $result->getName());
			Assert::same(TRUE, $result->isOk());
			Assert::same('ok', $result->getStatus());
			Assert::null($result->getError());
		};

		$checker->addServiceChecker(new RunningServiceChecker('dummy-1'));
		$checker->addServiceChecker(new RunningServiceChecker('dummy-2'));
		$checker->addServiceChecker(new RunningServiceChecker('dummy-3'));

		# all services and simple export mode
		$result = $checker->check([], HealthCheckerInterface::ARRAY_EXPORT_MODEL_SIMPLE);

		$testCommon($result);
		$this->compareResultAgainstArray($result, [
			'status' => 'ok',
			'is_ok' => TRUE,
		]);

		# all services and full export mode
		$result = $checker->check([], HealthCheckerInterface::ARRAY_EXPORT_MODE_FULL);

		$testCommon($result);
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
				[
					'name' => 'dummy-3',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
			],
		]);

		# some services and simple export mode
		$result = $checker->check(['dummy-1', 'dummy-3'], HealthCheckerInterface::ARRAY_EXPORT_MODEL_SIMPLE);

		$testCommon($result);
		$this->compareResultAgainstArray($result, [
			'status' => 'ok',
			'is_ok' => TRUE,
		]);

		# some services and full export mode
		$result = $checker->check(['dummy-1', 'dummy-3'], HealthCheckerInterface::ARRAY_EXPORT_MODE_FULL);

		$testCommon($result);
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
					'name' => 'dummy-3',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
			],
		]);
	}

	public function testCheckerShouldReturnFailedResult() : void
	{
		$checker = new HealthChecker();

		$testCommon = static function (ResultInterface $result) {
			Assert::same('health_check', $result->getName());
			Assert::same(FALSE, $result->isOk());
			Assert::same('failed', $result->getStatus());
			Assert::notNull($result->getError());

			Assert::exception(static function () use ($result) : void {
				throw $result->getError();
			}, MultipleResultsException::class, "Some health checks failed: \n[dummy-2]: The service \"dummy-2\" is down.");

		};

		$checker->addServiceChecker(new RunningServiceChecker('dummy-1'));
		$checker->addServiceChecker(new DownServiceChecker('dummy-2'));
		$checker->addServiceChecker(new RunningServiceChecker('dummy-3'));

		# all services and simple export mode
		$result = $checker->check([], HealthCheckerInterface::ARRAY_EXPORT_MODEL_SIMPLE);

		$testCommon($result);
		$this->compareResultAgainstArray($result, [
			'status' => 'failed',
			'is_ok' => FALSE,
		]);

		# all services and full export mode
		$result = $checker->check([], HealthCheckerInterface::ARRAY_EXPORT_MODE_FULL);

		$testCommon($result);
		$this->compareResultAgainstArray($result, [
			'status' => 'failed',
			'is_ok' => FALSE,
			'services' => [
				[
					'name' => 'dummy-1',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
				[
					'name' => 'dummy-2',
					'is_ok' => FALSE,
					'status' => 'down',
					'error' => 'The service "dummy-2" is down.',
				],
				[
					'name' => 'dummy-3',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
			],
		]);

		# some services and simple export mode
		$result = $checker->check(['dummy-1', 'dummy-2'], HealthCheckerInterface::ARRAY_EXPORT_MODEL_SIMPLE);

		$testCommon($result);
		$this->compareResultAgainstArray($result, [
			'status' => 'failed',
			'is_ok' => FALSE,
		]);

		# some services and full export mode
		$result = $checker->check(['dummy-1', 'dummy-2'], HealthCheckerInterface::ARRAY_EXPORT_MODE_FULL);

		$testCommon($result);
		$this->compareResultAgainstArray($result, [
			'status' => 'failed',
			'is_ok' => FALSE,
			'services' => [
				[
					'name' => 'dummy-1',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
				[
					'name' => 'dummy-2',
					'is_ok' => FALSE,
					'status' => 'down',
					'error' => 'The service "dummy-2" is down.',
				],
			],
		]);
	}

	public function testCheckerShouldReturnOkResultWhenDownServiceIsOmitted() : void
	{
		$checker = new HealthChecker();

		$testCommon = static function (ResultInterface $result) {
			Assert::same('health_check', $result->getName());
			Assert::same(TRUE, $result->isOk());
			Assert::same('ok', $result->getStatus());
			Assert::null($result->getError());
		};

		$checker->addServiceChecker(new RunningServiceChecker('dummy-1'));
		$checker->addServiceChecker(new DownServiceChecker('dummy-2')); # this service is omitted
		$checker->addServiceChecker(new RunningServiceChecker('dummy-3'));

		# simple export mode
		$result = $checker->check(['dummy-1', 'dummy-3'], HealthCheckerInterface::ARRAY_EXPORT_MODEL_SIMPLE);

		$testCommon($result);
		$this->compareResultAgainstArray($result, [
			'status' => 'ok',
			'is_ok' => TRUE,
		]);

		# full export mode
		$result = $checker->check(['dummy-1', 'dummy-3'], HealthCheckerInterface::ARRAY_EXPORT_MODE_FULL);

		$testCommon($result);
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
					'name' => 'dummy-3',
					'is_ok' => TRUE,
					'status' => 'running',
					'error' => NULL,
				],
			],
		]);
	}

	private function compareResultAgainstArray(ResultInterface $result, array $array) : void
	{
		Assert::equal($array, $result->toArray());
		Assert::equal(json_encode($array, JSON_THROW_ON_ERROR), json_encode($result, JSON_THROW_ON_ERROR));
	}
}

(new HealthCheckerTestCase())->run();
