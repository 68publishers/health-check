<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Omni\Application\ServiceChecker;

use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;
use SixtyEightPublishers\CoreBundle\Contract\ServiceConnection\ServiceConnectionPoolInterface;
use SixtyEightPublishers\CoreBundle\Contract\ServiceConnection\UnableToCreateConnectionException;
use function sprintf;

final class ServiceConnectionChecker implements ServiceCheckerInterface
{
	private readonly string $serviceCheckerName;

	public function __construct(
		private readonly ServiceConnectionPoolInterface $serviceConnectionPool,
		private readonly string $serviceType,
		private readonly ?string $serviceName = null,
		?string $serviceCheckerName = null,
	) {
		$this->serviceCheckerName = $serviceCheckerName ?? ($this->serviceType . '@' . ($this->serviceName ?? 'default'));
	}

	public function getName(): string
	{
		return $this->serviceCheckerName;
	}

	public function check(): ResultInterface
	{
		try {
			$connection = $this->serviceConnectionPool->getConnection(
				serviceType: $this->serviceType,
				name: $this->serviceName,
			);
		} catch (UnableToCreateConnectionException $e) {
			return ServiceResult::createError(
				serviceName: $this->getName(),
				detail: [],
				error: new HealthCheckException(
					message: $e->getMessage(),
					previous: $e,
				),
			);
		}

		$result = $connection->performHealthCheck();

		return $result->isOk()
			? ServiceResult::createOk(
				serviceName: $this->getName(),
				detail: $result->extra,
			)
			: ServiceResult::createError(
				serviceName: $this->getName(),
				detail: $result->extra,
				error: new HealthCheckException(
					message: sprintf(
						'Service respond with status code %d.',
						$result->statusCode,
					),
				),
			);
	}
}
