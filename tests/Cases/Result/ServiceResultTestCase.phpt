<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Cases\Result;

use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

require __DIR__ . '/../../bootstrap.php';

class ServiceResultTestCase extends TestCase
{
	public function testOkServiceResult() : void
	{
		$result = new ServiceResult('dummy-1', TRUE, 'running', NULL);
		$resultFromFactoryMethod = ServiceResult::createOk('dummy-1', 'running');

		$this->runTests($result, 'dummy-1', TRUE, 'running', NULL);
		$this->runTests($resultFromFactoryMethod, 'dummy-1', TRUE, 'running', NULL);
	}

	public function testFailedServiceResult() : void
	{
		$e = new HealthCheckException('The service is down.');
		$result = new ServiceResult('dummy-2', FALSE, 'down', $e);
		$resultFromFactoryMethod = ServiceResult::createError('dummy-2', 'down', $e);

		$this->runTests($result, 'dummy-2', FALSE, 'down', $e);
		$this->runTests($resultFromFactoryMethod, 'dummy-2', FALSE, 'down', $e);
	}

	private function runTests(ServiceResult $result, string $name, bool $ok, string $status, ?HealthCheckExceptionInterface $error) : void
	{
		Assert::same($name, $result->getName());
		Assert::same($ok, $result->isOk());
		Assert::same($status, $result->getStatus());
		Assert::same($error, $result->getError());

		$expectedArray = [
			'name' => $name,
			'is_ok' => $ok,
			'status' => $status,
			'error' => NULL !== $error ? $error->getMessage() : NULL,
		];

		Assert::equal($expectedArray, $result->toArray());
		Assert::equal(json_encode($expectedArray, JSON_THROW_ON_ERROR), json_encode($result, JSON_THROW_ON_ERROR));
	}
}

(new ServiceResultTestCase())->run();
