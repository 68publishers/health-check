<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\ServiceChecker;

use PDO;
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
		$checker = new PDOServiceChecker(
			dsn: $this->createDsn(),
			user: $_ENV['POSTGRES_USER'],
			password: $_ENV['POSTGRES_PASSWORD'],
		);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::true($result->isOk());
		Assert::same([], $result->getDetail());
		Assert::null($result->getError());
	}

	public function testServiceShouldBeHealthyWithTable(): void
	{
		$pdo = new PDO(
			dsn: $this->createDsn(),
			username: $_ENV['POSTGRES_USER'],
			password: $_ENV['POSTGRES_PASSWORD'],
		);
		$pdo->setAttribute(attribute: PDO::ATTR_ERRMODE, value: PDO::ERRMODE_EXCEPTION);

		try {
			$pdo->query('CREATE TABLE IF NOT EXISTS test_pdo (id UUID NOT NULL, content TEXT NOT NULL, PRIMARY KEY (id))')?->execute();
			$insert = $pdo->prepare('INSERT INTO test_pdo (id, content) VALUES (:id, :content)');

			$insert->execute(['id' => 'bae76a02-429f-472f-80b2-d4e4a1d174a9', 'content' => 'row 1']);
			$insert->execute(['id' => '96aa090e-c97b-4a37-ad1e-deee507d0372', 'content' => 'row 2']);
			$insert->execute(['id' => '234be3cf-b11e-49a7-b84e-4658ff2fb17a', 'content' => 'row 3']);

			$checker = new PDOServiceChecker(
				dsn: $this->createDsn(),
				user: $_ENV['POSTGRES_USER'],
				password: $_ENV['POSTGRES_PASSWORD'],
				table: 'test_pdo',
			);
			$result = $checker->check();

			Assert::same('database', $checker->getName());
			Assert::same('database', $result->getName());
			Assert::true($result->isOk());
			Assert::same([
				'rows' => [
					['id' => 'bae76a02-429f-472f-80b2-d4e4a1d174a9', 'content' => 'row 1'],
					['id' => '96aa090e-c97b-4a37-ad1e-deee507d0372', 'content' => 'row 2'],
					['id' => '234be3cf-b11e-49a7-b84e-4658ff2fb17a', 'content' => 'row 3'],
				],
			], $result->getDetail());
			Assert::null($result->getError());
		} finally {
			$pdo->query('DROP TABLE IF EXISTS test_pdo')?->execute();
		}
	}

	public function testServiceShouldByUnhealthyOnInvalidPassword(): void
	{
		$checker = new PDOServiceChecker(
			dsn: $this->createDsn(),
			user: $_ENV['POSTGRES_USER'],
			password: 'invalid-password',
		);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::false($result->isOk());
		Assert::same([], $result->getDetail());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::match('%A?%authentication failed for user "root"%A?%', $result->getError()->getMessage());
	}

	public function testServiceShouldByUnhealthyOnInvalidPort(): void
	{
		$checker = new PDOServiceChecker(
			dsn: $this->createDsn(null, 1000),
			user: $_ENV['POSTGRES_USER'],
			password: $_ENV['POSTGRES_PASSWORD'],
		);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::false($result->isOk());
		Assert::same([], $result->getDetail());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::match('%A?%Connection refused%A?%', $result->getError()->getMessage());
	}

	public function testServiceShouldByUnhealthyOnInvalidDatabaseName(): void
	{
		$checker = new PDOServiceChecker(
			dsn: $this->createDsn(null, null, 'invalid-database'),
			user: $_ENV['POSTGRES_USER'],
			password: $_ENV['POSTGRES_PASSWORD'],
		);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::false($result->isOk());
		Assert::same([], $result->getDetail());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::match('%A?%database "invalid-database" does not exist%A?%', $result->getError()->getMessage());
	}

	public function testServiceShouldByUnhealthyOnInvalidTableName(): void
	{
		$checker = new PDOServiceChecker(
			dsn: $this->createDsn(),
			user: $_ENV['POSTGRES_USER'],
			password: $_ENV['POSTGRES_PASSWORD'],
			table: 'missing-table',
		);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::false($result->isOk());
		Assert::same([], $result->getDetail());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::match('%A?%relation "missing-table" does not exist%A?%', $result->getError()->getMessage());
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
			Assert::null($checker->table);
			Assert::same('database', $checker->getName());
		}, null, PDOServiceChecker::class));
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
			'table' => 'test',
			'serviceName' => 'db',
		]);
		$dsn = $this->createDsn('host', 123, 'db');

		call_user_func(Closure::bind(static function () use ($checker, $dsn) {
			Assert::same($dsn, $checker->dsn);
			Assert::same('root', $checker->user);
			Assert::same('pass', $checker->password);
			Assert::same(['opt' => 'val'], $checker->options);
			Assert::same('test', $checker->table);
			Assert::same('db', $checker->getName());
		}, null, PDOServiceChecker::class));
	}

	private function createDsn(?string $host = null, string|int|null $port = null, ?string $dbName = null): string
	{
		$host = $host ?? $_ENV['POSTGRES_HOST'];
		$port = $port ?? $_ENV['POSTGRES_PORT'];
		$dbName = $dbName ?? $_ENV['POSTGRES_DB'];

		return "pgsql:host=$host;port=$port;dbname=$dbName;";
	}
}

(new PDOServiceCheckerTest())->run();
