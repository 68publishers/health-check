<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\ServiceChecker;

use Tester\Assert;
use Tester\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\ServiceChecker\DbalConnectionServiceChecker;

require __DIR__ . '/../bootstrap.php';

final class DbalConnectionServiceCheckerTest extends TestCase
{
	public function testServiceShouldBeHealthy(): void
	{
		$checker = new DbalConnectionServiceChecker(
			connection: $this->createConnection(),
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
		$connection = $this->createConnection();

		try {
			$connection->executeStatement('CREATE TABLE IF NOT EXISTS test_dbal (id UUID NOT NULL, content TEXT NOT NULL, PRIMARY KEY (id))');
			$insert = $connection->createQueryBuilder()->insert('test_dbal')->values(['id' => ':id', 'content' => ':content']);

			$insert->setParameters(['id' => 'bae76a02-429f-472f-80b2-d4e4a1d174a9', 'content' => 'row 1'])->executeStatement();
			$insert->setParameters(['id' => '96aa090e-c97b-4a37-ad1e-deee507d0372', 'content' => 'row 2'])->executeStatement();
			$insert->setParameters(['id' => '234be3cf-b11e-49a7-b84e-4658ff2fb17a', 'content' => 'row 3'])->executeStatement();

			$checker = new DbalConnectionServiceChecker(
				connection: $this->createConnection(),
				table: 'test_dbal'
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
			$connection->executeStatement('DROP TABLE IF EXISTS test_dbal');
		}
	}

	public function testServiceShouldByUnhealthyOnDbalException(): void
	{
		$checker = new DbalConnectionServiceChecker(
			connection: $this->createConnection(dbname: 'missing-database'),
		);
		$result = $checker->check();

		Assert::same('database', $checker->getName());
		Assert::same('database', $result->getName());
		Assert::false($result->isOk());
		Assert::same([], $result->getDetail());
		Assert::type(HealthCheckException::class, $result->getError());
		Assert::match('%A?%database "missing-database" does not exist%A?%', $result->getError()->getMessage());
	}

	private function createConnection(?string $dbname = null): Connection
	{
		return DriverManager::getConnection(
			params: [
				'driver' => 'pdo_pgsql',
				'dbname' => $dbname ?? $_ENV['POSTGRES_DB'],
				'host' =>  $_ENV['POSTGRES_HOST'],
				'port' => (int) $_ENV['POSTGRES_PORT'],
				'user' => $_ENV['POSTGRES_USER'],
				'password' => $_ENV['POSTGRES_PASSWORD'],
			],
		);
	}
}

(new DbalConnectionServiceCheckerTest())->run();
