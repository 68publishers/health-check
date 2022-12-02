<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck;

use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;

interface HealthCheckerInterface
{
	public function addServiceChecker(ServiceCheckerInterface $serviceChecker): void;

	/**
	 * @param array<string>|null $servicesOnly
	 */
	public function check(?array $servicesOnly = NULL, ?ExportMode $exportMode = NULL): ResultInterface;
}
