<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck;

use InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\Result\PingResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\PingReceiver\PingReceiverInterface;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;
use function sprintf;
use function array_keys;

final class HealthChecker implements HealthCheckerInterface
{
	/** @var array<ServiceCheckerInterface> */
	private array $serviceCheckers = [];

	public function __construct(
		private readonly ExportModeResolverInterface $exportModeResolver = new StaticExportModeResolver(ExportMode::Simple),
		private readonly ?PingReceiverInterface $pingReceiver = null,
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

	public function check(?array $servicesOnly = null, ExportMode|string|null $exportMode = null): ResultInterface
	{
		$check = function (?array $servicesOnly, ExportMode $exportMode): ResultInterface {
			$result = new HealthCheckResult();
			$servicesOnly = $servicesOnly ?? array_keys($this->serviceCheckers);

			foreach ($servicesOnly as $serviceName) {
				if (!isset($this->serviceCheckers[$serviceName])) {
					throw new InvalidArgumentException(sprintf(
						'Checker for the service named "%s" id not defined.',
						$serviceName
					));
				}

				$result = $result->withResult($this->serviceCheckers[$serviceName]->check());
			}

			return $result->withExportMode($exportMode);
		};

		if ('ping' === $exportMode) {
			if (null === $this->pingReceiver) {
				return new PingResult(
					ok: false,
				);
			}

			return $this->pingReceiver->pingUsing(
				closure: fn (): ResultInterface => $check($servicesOnly, ExportMode::Full),
			);
		}

		$exportMode = $exportMode instanceof ExportMode ? $exportMode : (null !== $exportMode ? ExportMode::tryFrom($exportMode) : null);

		return $check($servicesOnly, $exportMode ?? $this->exportModeResolver->resolve());
	}
}
