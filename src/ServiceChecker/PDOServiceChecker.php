<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use PDO;
use PDOException;
use InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use function sprintf;
use function str_replace;

final class PDOServiceChecker implements ServiceCheckerInterface
{
	/**
	 * @param array<string, mixed> $options
	 */
	public function __construct(
		private readonly string $dsn,
		private readonly ?string $user = null,
		private readonly ?string $password = null,
		private readonly array $options = [],
		private readonly ?string $table = null,
		private readonly string $serviceName = 'database',
	) {
	}

	/**
	 * @param array{
	 *     driver?: string,
	 *     host?: string,
	 *     port?: int|string,
	 *     dbname?: string,
	 *     user?: string|null,
	 *     password?: string|null,
	 *     options?: array<string, mixed>|null,
	 *     table?: string|null,
	 *     serviceName?: string|null,
	 * } $params
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

		/** @var array{driver: string, host: string, port: int|string, dbname: string, user?: string|null, password?: string|null, options?: array<string, mixed>|null, table?: string|null, serviceName?: string|null} $params */

		return new self(
			dsn: sprintf(
				'%s:host=%s;port=%s;dbname=%s;',
				$params['driver'],
				$params['host'],
				$params['port'],
				$params['dbname']
			),
			user:$params['user'] ?? null,
			password: $params['password'] ?? null,
			options: $params['options'] ?? [],
			table: $params['table'] ?? null,
			serviceName: $params['serviceName'] ?? 'database'
		);
	}

	public function getName(): string
	{
		return $this->serviceName;
	}

	public function check(): ResultInterface
	{
		try {
			$pdo = new PDO(
				dsn: $this->dsn,
				username: $this->user,
				password: $this->password,
				options: $this->options,
			);

			$pdo->setAttribute(attribute: PDO::ATTR_ERRMODE, value: PDO::ERRMODE_EXCEPTION);
			$detail = [];

			if (null !== $this->table) {
				$table = '"' . str_replace('"', '""', $this->table) . '"';
				$statement = $pdo->query("SELECT * FROM $table;") ?: null;
				$detail['rows'] = $statement?->fetchAll(PDO::FETCH_ASSOC) ?? [];
			} else {
				$statement = $pdo->query('SELECT 1;') ?: null;
				$statement?->fetch();
			}

			return ServiceResult::createOk(
				serviceName: $this->getName(),
				detail: $detail,
			);
		} catch (PDOException $e) {
			return ServiceResult::createError(
				serviceName: $this->getName(),
				detail: [],
				error: new HealthCheckException(
					message: $e->getMessage(),
					previous: $e,
				),
			);
		}
	}
}
