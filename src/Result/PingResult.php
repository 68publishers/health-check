<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

final class PingResult implements ResultInterface
{
	public function __construct(
		public readonly bool $ok,
	) {
	}

	public function getName(): string
	{
		return 'ping';
	}

	public function isOk(): bool
	{
		return $this->ok;
	}

	public function getDetail(): array
	{
		return [];
	}

	public function getError(): ?HealthCheckExceptionInterface
	{
		return null;
	}

	/**
	 * @return array{is_ok: bool}
	 */
	public function toArray(): array
	{
		return [
			'is_ok' => $this->isOk(),
		];
	}

	/**
	 * @return array{is_ok: bool}
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
