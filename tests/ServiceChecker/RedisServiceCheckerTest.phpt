<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\ServiceChecker;

use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\ServiceChecker\RedisServiceChecker;

require __DIR__ . '/../bootstrap.php';

final class RedisServiceCheckerTest extends TestCase
{
	public function testRedis5ServiceShouldBeHealthy(): void
	{
		$checker = new RedisServiceChecker($_ENV['REDIS5_HOST'], $_ENV['REDIS5_PORT'], $_ENV['REDIS5_PASSWORD']);
		$result = $checker->check();

		Assert::same('redis', $checker->getName());
		Assert::same('redis', $result->getName());
		Assert::true($result->isOk());
		Assert::same('running', $result->getStatus());
		Assert::null($result->getError());
	}

	public function testRedis6ServiceShouldBeHealthy(): void
	{
		$checker = new RedisServiceChecker($_ENV['REDIS6_HOST'], $_ENV['REDIS6_PORT'], [$_ENV['REDIS6_USER'], $_ENV['REDIS6_PASSWORD']]);
		$result = $checker->check();

		Assert::same('redis', $checker->getName());
		Assert::same('redis', $result->getName());
		Assert::true($result->isOk());
		Assert::same('running', $result->getStatus());
		Assert::null($result->getError());
	}

	public function testRedis5ServiceShouldBeUnhealthyIfPasswordIsInvalid(): void
	{
		$checker = new RedisServiceChecker($_ENV['REDIS5_HOST'], $_ENV['REDIS5_PORT'], 'invalid-password');
		$result = $checker->check();

		Assert::same('redis', $checker->getName());
		Assert::same('redis', $result->getName());
		Assert::false($result->isOk());
		Assert::same('unauthorized', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::same('Failed to auth a connection.', $result->getError()->getMessage());
	}

	public function testRedis6ServiceShouldBeUnhealthyIfPasswordIsInvalid(): void
	{
		$checker = new RedisServiceChecker($_ENV['REDIS6_HOST'], $_ENV['REDIS6_PORT'], [$_ENV['REDIS6_USER'], 'invalid-password']);
		$result = $checker->check();

		Assert::same('redis', $checker->getName());
		Assert::same('redis', $result->getName());
		Assert::false($result->isOk());
		Assert::same('unauthorized', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::same('Failed to auth a connection.', $result->getError()->getMessage());
	}

	public function testRedis5ServiceShouldBeUnhealthyIfConnectionRefused(): void
	{
		$checker = new RedisServiceChecker($_ENV['REDIS5_HOST'], 1000, $_ENV['REDIS5_PASSWORD']);
		$result = $checker->check();

		Assert::same('redis', $checker->getName());
		Assert::same('redis', $result->getName());
		Assert::false($result->isOk());
		Assert::same('down', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::same('Connection refused', $result->getError()->getMessage());
	}

	public function testRedis6ServiceShouldBeUnhealthyIfConnectionRefused(): void
	{
		$checker = new RedisServiceChecker($_ENV['REDIS6_HOST'], 1000, [$_ENV['REDIS6_USER'], $_ENV['REDIS6_PASSWORD']]);
		$result = $checker->check();

		Assert::same('redis', $checker->getName());
		Assert::same('redis', $result->getName());
		Assert::false($result->isOk());
		Assert::same('down', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::same('Connection refused', $result->getError()->getMessage());
	}
}

(new RedisServiceCheckerTest())->run();
