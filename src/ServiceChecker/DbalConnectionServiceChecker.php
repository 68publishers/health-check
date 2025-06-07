<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;

final class DbalConnectionServiceChecker implements ServiceCheckerInterface
{
	public function __construct(
		private readonly Connection $connection,
		private readonly ?string $table = null,
		private readonly string $serviceName = 'database',
	) {
	}

	public function getName(): string
	{
		return $this->serviceName;
	}

	public function check(): ResultInterface
	{
		try {
			$detail = [];

			if (null !== $this->table) {
				$detail['rows'] = $this->connection->createQueryBuilder()->select('*')->from($this->table)->fetchAllAssociative();
			} else {
				$this->connection->executeQuery('SELECT 1')->fetchOne();
			}

			return ServiceResult::createOk(
				serviceName: $this->getName(),
				detail: $detail,
			);
		} catch (DbalException $e) {
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
