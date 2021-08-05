<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use SixtyEightPublishers\HealthCheck\Result\ResultInterface;

interface ServiceCheckerInterface
{
	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return \SixtyEightPublishers\HealthCheck\Result\ResultInterface
	 */
	public function check(): ResultInterface;
}
