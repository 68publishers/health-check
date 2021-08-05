<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck;

use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\Exception\InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;

final class HealthChecker implements HealthCheckerInterface
{
	/** @var \SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface[]  */
	private array $serviceCheckers = [];

	/**
	 * {@inheritDoc}
	 */
	public function addServiceChecker(ServiceCheckerInterface $serviceChecker): void
	{
		$name = $serviceChecker->getName();

		if (isset($this->serviceCheckers[$name])) {
			throw new InvalidArgumentException(sprintf(
				'The service checker with name "%s" is already registered.',
				$serviceChecker->getName()
			));
		}

		$this->serviceCheckers[$name] = $serviceChecker;
	}

	/**
	 * {@inheritDoc}
	 */
	public function check(array $services = [], string $arrayExportMode = self::ARRAY_EXPORT_MODEL_SIMPLE): ResultInterface
	{
		$result = new HealthCheckResult();
		$services = empty($services) ? array_keys($this->serviceCheckers) : $services;

		$result->setArrayExportMode($arrayExportMode);

		foreach ($services as $serviceName) {
			if (!isset($this->serviceCheckers[$serviceName])) {
				throw new InvalidArgumentException(sprintf(
					'Checker for the service named "%s" id not defined.',
					$serviceName
				));
			}

			$result = $result->withResult($this->serviceCheckers[$serviceName]->check());
		}

		return $result;
	}
}
