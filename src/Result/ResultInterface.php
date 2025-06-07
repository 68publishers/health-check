<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use JsonSerializable;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

interface ResultInterface extends JsonSerializable
{
	public function getName(): string;

	public function isOk(): bool;

	/**
	 * @return array<string, mixed>
	 */
	public function getDetail(): array;

	public function getError(): ?HealthCheckExceptionInterface;

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;
}
