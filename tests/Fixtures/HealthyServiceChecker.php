<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Fixtures;

use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;

final class HealthyServiceChecker implements ServiceCheckerInterface
{
	public function __construct(
		private readonly string $name,
	) {
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function check(): ResultInterface
	{
		return ServiceResult::createOk($this->name, 'healthy');
	}
}
