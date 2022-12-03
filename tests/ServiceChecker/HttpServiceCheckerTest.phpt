<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\ServiceChecker;

use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\ServiceChecker\HttpServiceChecker;

require __DIR__ . '/../bootstrap.php';

final class HttpServiceCheckerTest extends TestCase
{
	public function testServiceShouldBeHealthy(): void
	{
		$checker = new HttpServiceChecker('test', $_ENV['WEB_SERVICE_HOST'] . '/200');
		$result = $checker->check();

		Assert::same('test', $checker->getName());
		Assert::same('test', $result->getName());
		Assert::true($result->isOk());
		Assert::same('running', $result->getStatus());
		Assert::null($result->getError());
	}

	public function testServiceShouldBeUnhealthyIfStatusCodeIsNot200(): void
	{
		$checker = new HttpServiceChecker('test', $_ENV['WEB_SERVICE_HOST'] . '/503');
		$result = $checker->check();

		Assert::same('test', $checker->getName());
		Assert::same('test', $result->getName());
		Assert::false($result->isOk());
		Assert::same('down', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::same('Server respond with the status header HTTP/1.1 503 Service Unavailable', $result->getError()->getMessage());
	}

	public function testServiceShouldBeUnhealthyIfInvalidUrlPassed(): void
	{
		$checker = new HttpServiceChecker('test', 'invalid-url');
		$result = $checker->check();

		Assert::same('test', $checker->getName());
		Assert::same('test', $result->getName());
		Assert::false($result->isOk());
		Assert::same('down', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::same('Can\'t fetch a response.', $result->getError()->getMessage());
	}
}

(new HttpServiceCheckerTest())->run();
