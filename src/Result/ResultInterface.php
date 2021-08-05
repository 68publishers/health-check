<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use JsonSerializable;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

interface ResultInterface extends JsonSerializable
{
	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return bool
	 */
	public function isOk(): bool;

	/**
	 * @return string
	 */
	public function getStatus(): string;

	/**
	 * @return \SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface|NULL
	 */
	public function getError(): ?HealthCheckExceptionInterface;

	/**
	 * @return array
	 */
	public function toArray(): array;
}
