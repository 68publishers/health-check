<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

final class ServiceResult implements ResultInterface
{
	private function __construct(
		private readonly string $serviceName,
		private readonly bool $ok,
		private readonly string $status,
		private readonly ?HealthCheckExceptionInterface $error = null,
	) {
	}

	public static function createOk(string $serviceName, string $status = 'running'): self
	{
		return new self($serviceName, true, $status);
	}

	public static function createError(string $serviceName, string $status, HealthCheckExceptionInterface $error): self
	{
		return new self($serviceName, false, $status, $error);
	}

	public function getName(): string
	{
		return $this->serviceName;
	}

	public function isOk(): bool
	{
		return $this->ok;
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function getError(): ?HealthCheckExceptionInterface
	{
		return $this->error;
	}

	/**
	 * @return array{name: string, is_ok: bool, status: string, error: ?string}
	 */
	public function toArray(): array
	{
		return [
			'name' => $this->getName(),
			'is_ok' => $this->isOk(),
			'status' => $this->getStatus(),
			'error' => $this->getError()?->getMessage(),
		];
	}

	/**
	 * @return array{name: string, is_ok: bool, status: string, error: ?string}
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
