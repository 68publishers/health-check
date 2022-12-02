<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck;

use InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;
use function array_keys;

final class HealthChecker implements HealthCheckerInterface
{
	/** @var array<ServiceCheckerInterface> */
	private array $serviceCheckers = [];

	public function __construct(
		private readonly ExportModeResolverInterface $exportModeResolver = new StaticExportModeResolver(ExportMode::Simple),
	) {
	}

	public function addServiceChecker(ServiceCheckerInterface $serviceChecker): void
	{
		$name = $serviceChecker->getName();

		if (isset($this->serviceCheckers[$name])) {
			throw new InvalidArgumentException(sprintf(
				'Service checker with the name "%s" is already registered.',
				$serviceChecker->getName()
			));
		}

		$this->serviceCheckers[$name] = $serviceChecker;
	}

	public function check(?array $servicesOnly = NULL, ?ExportMode $exportMode = NULL): ResultInterface
	{
		$result = new HealthCheckResult();
		$servicesOnly = $servicesOnly ?? array_keys($this->serviceCheckers);

		foreach ($servicesOnly as $serviceName) {
			if (!isset($this->serviceCheckers[$serviceName])) {
				throw new InvalidArgumentException(\sprintf(
					'Checker for the service named "%s" id not defined.',
					$serviceName
				));
			}

			$result = $result->withResult($this->serviceCheckers[$serviceName]->check());
		}

		return $result->withExportMode($exportMode ?? $this->exportModeResolver->resolve());
	}
}
