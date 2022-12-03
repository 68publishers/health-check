<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\ServiceChecker;

use Closure;
use Tester\Assert;
use Tester\TestCase;
use InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\ServiceChecker\PDOServiceChecker;

require __DIR__ . '/../bootstrap.php';

final class PDOServiceCheckerTest extends TestCase
{
	public function testServiceShouldBeHealthy(): void
	{
		$checker = new PDOServiceChecker($this->createDsn(), $_ENV['POSTGRES_USER'], $_ENV['POSTGRES_PASSWORD']);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::true($result->isOk());
		Assert::same('running', $result->getStatus());
		Assert::null($result->getError());
	}

	public function testServiceShouldByUnhealthyOnInvalidPassword(): void
	{
		$checker = new PDOServiceChecker($this->createDsn(), $_ENV['POSTGRES_USER'], 'invalid-password');
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::false($result->isOk());
		Assert::same('down', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::match('%A?%authentication failed for user "root"%A?%', $result->getError()->getMessage());
	}

	public function testServiceShouldByUnhealthyOnInvalidPort(): void
	{
		$checker = new PDOServiceChecker($this->createDsn(NULL, 1000), $_ENV['POSTGRES_USER'], $_ENV['POSTGRES_PASSWORD']);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::false($result->isOk());
		Assert::same('down', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::match('%A?%Connection refused%A?%', $result->getError()->getMessage());
	}

	public function testServiceShouldByUnhealthyOnInvalidDatabaseName(): void
	{
		$checker = new PDOServiceChecker($this->createDsn(NULL, NULL, 'invalid-database'), $_ENV['POSTGRES_USER'], $_ENV['POSTGRES_PASSWORD']);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::false($result->isOk());
		Assert::same('down', $result->getStatus());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::match('%A?%database "invalid-database" does not exist%A?%', $result->getError()->getMessage());
	}

	public function testExceptionShouldBeThrownIfDriverKeyMissing(): void
	{
		Assert::exception(
			static fn () => PDOServiceChecker::fromParams(['host' => 'host', 'port' => 123, 'dbname' => 'db']),
			InvalidArgumentException::class,
			'Missing required parameter "driver".'
		);
	}

	public function testExceptionShouldBeThrownIfHostKeyMissing(): void
	{
		Assert::exception(
			static fn () => PDOServiceChecker::fromParams(['driver' => 'pgsql', 'port' => 123, 'dbname' => 'db']),
			InvalidArgumentException::class,
			'Missing required parameter "host".'
		);
	}

	public function testExceptionShouldBeThrownIfPortKeyMissing(): void
	{
		Assert::exception(
			static fn () => PDOServiceChecker::fromParams(['driver' => 'pgsql', 'host' => 'host', 'dbname' => 'db']),
			InvalidArgumentException::class,
			'Missing required parameter "port".'
		);
	}

	public function testExceptionShouldBeThrownIfDbnameKeyMissing(): void
	{
		Assert::exception(
			static fn () => PDOServiceChecker::fromParams(['driver' => 'pgsql', 'host' => 'host', 'port' => 123]),
			InvalidArgumentException::class,
			'Missing required parameter "dbname".'
		);
	}

	public function testCheckerShouldBeCreatedWithRequiredParamsOnly(): void
	{
		$checker = PDOServiceChecker::fromParams([
			'driver' => 'pgsql',
			'host' => 'host',
			'port' => 123,
			'dbname' => 'db',
		]);
		$dsn = $this->createDsn('host', 123, 'db');

		call_user_func(Closure::bind(static function () use ($checker, $dsn) {
			Assert::same($dsn, $checker->dsn);
			Assert::null($checker->user);
			Assert::null($checker->password);
			Assert::same([], $checker->options);
		}, NULL, PDOServiceChecker::class));
	}

	public function testCheckerShouldBeCreatedWithOptionalParameters(): void
	{
		$checker = PDOServiceChecker::fromParams([
			'driver' => 'pgsql',
			'host' => 'host',
			'port' => 123,
			'dbname' => 'db',
			'user' => 'root',
			'password' => 'pass',
			'options' => [
				'opt' => 'val',
			],
		]);
		$dsn = $this->createDsn('host', 123, 'db');

		call_user_func(Closure::bind(static function () use ($checker, $dsn) {
			Assert::same($dsn, $checker->dsn);
			Assert::same('root', $checker->user);
			Assert::same('pass', $checker->password);
			Assert::same(['opt' => 'val'], $checker->options);
		}, NULL, PDOServiceChecker::class));
	}

	private function createDsn(?string $host = NULL, string|int|NULL $port = NULL, ?string $dbName = NULL): string
	{
		$host = $host ?? $_ENV['POSTGRES_HOST'];
		$port = $port ?? $_ENV['POSTGRES_PORT'];
		$dbName = $dbName ?? $_ENV['POSTGRES_DB'];

		return "pgsql:host=$host;port=$port;dbname=$dbName;";
	}
}

(new PDOServiceCheckerTest())->run();
