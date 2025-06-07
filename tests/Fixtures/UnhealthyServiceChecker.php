<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Fixtures;

use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;

final class UnhealthyServiceChecker implements ServiceCheckerInterface
{
	/**
	 * @param array<string, mixed> $detail
	 * @param list<string>         $groups
	 */
	public function __construct(
		private readonly string $serviceName,
		private readonly array  $detail = [],
		private readonly array  $groups = ['default'],
	) {
	}

	public function getName(): string
	{
		return $this->serviceName;
	}

	public function getGroups(): array
	{
		return $this->groups;
	}

	public function check(): ResultInterface
	{
		return ServiceResult::createError(
			serviceName: $this->serviceName,
			detail: $this->detail,
			error: new HealthCheckException('Service is unhealthy.'),
		);
	}
}
