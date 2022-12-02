<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use PDO;
use PDOException;
use InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;

final class PDOServiceChecker implements ServiceCheckerInterface
{
	/**
	 * @param array<string, mixed> $options
	 */
	public function __construct(
		private readonly string $dsn,
		private readonly ?string $user = NULL,
		private readonly ?string $password = NULL,
		private readonly array $options = [],
		private readonly string $serviceName = 'database',
	) {
	}

	/**
	 * @param array{driver: ?string, host: ?string, port: null|int|string, dbname: ?string, user?: ?string, password?: ?string, options?: ?array<string, mixed>} $params
	 */
	public static function fromParams(array $params): self
	{
		foreach (['driver', 'host', 'port', 'dbname'] as $key) {
			if (!isset($params[$key])) {
				throw new InvalidArgumentException(sprintf(
					'Missing required parameter "%s".',
					$key
				));
			}
		}

		return new self(
			\sprintf(
				'%s:host=%s;port=%s;dbname=%s;',
				$params['driver'],
				$params['host'],
				$params['port'],
				$params['dbname']
			),
			$params['user'] ?? NULL,
			$params['password'] ?? NULL,
			$params['options'] ?? []
		);
	}

	public function getName(): string
	{
		return $this->serviceName;
	}

	public function check(): ResultInterface
	{
		try {
			$pdo = new PDO($this->dsn, $this->user, $this->password, $this->options);

			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$statement = $pdo->query('SELECT 1;');

			if (FALSE !== $statement) {
				$statement->execute();
			}

			return ServiceResult::createOk($this->getName());
		} catch (PDOException $e) {
			return ServiceResult::createError($this->getName(), 'down', new HealthCheckException($e->getMessage(), $e->getCode(), $e));
		}
	}
}
