<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Fixture\ServiceChecker;

use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;

final class RunningServiceChecker implements ServiceCheckerInterface
{
	private string $name;

	/**
	 * @param string $name
	 */
	public function __construct(string $name)
	{
		$this->name = $name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function check(): ResultInterface
	{
		return ServiceResult::createOk($this->getName(), 'running');
	}
}
