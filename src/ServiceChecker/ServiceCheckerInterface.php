<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use SixtyEightPublishers\HealthCheck\Result\ResultInterface;

interface ServiceCheckerInterface
{
	public function getName(): string;

	public function check(): ResultInterface;
}
