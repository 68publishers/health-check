<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck;

use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;

interface HealthCheckerInterface
{
	public const ARRAY_EXPORT_MODEL_SIMPLE = HealthCheckResult::ARRAY_EXPORT_MODEL_SIMPLE;
	public const ARRAY_EXPORT_MODE_FULL = HealthCheckResult::ARRAY_EXPORT_MODE_FULL;

	/**
	 * @param \SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface $serviceChecker
	 *
	 * @return void
	 */
	public function addServiceChecker(ServiceCheckerInterface $serviceChecker): void;

	/**
	 * An empty array => all services
	 *
	 * @param array  $services
	 * @param string $arrayExportMode
	 *
	 * @return \SixtyEightPublishers\HealthCheck\Result\ResultInterface
	 */
	public function check(array $services = [], string $arrayExportMode = self::ARRAY_EXPORT_MODEL_SIMPLE): ResultInterface;
}
