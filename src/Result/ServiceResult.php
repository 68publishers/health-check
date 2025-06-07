<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

/**
 * @phpstan-type ServiceResultArray = array{
 *     name: string,
 *     is_ok: bool,
 *     detail: array<string, mixed>,
 *     error: string|null,
 * }
 * @phpstan-type ServiceResultJson = array{
 *     name: string,
 *     is_ok: bool,
 *     detail: object,
 *     error: string|null,
 * }
 */
final class ServiceResult implements ResultInterface
{
	/**
	 * @param array<string, mixed> $detail
	 */
	private function __construct(
		private readonly string                         $serviceName,
		private readonly bool                           $ok,
		private readonly array                          $detail,
		private readonly ?HealthCheckExceptionInterface $error = null,
	) {
	}

	/**
	 * @param array<string, mixed> $detail
	 */
	public static function createOk(string $serviceName, array $detail): self
	{
		return new self($serviceName, true, $detail);
	}

	/**
	 * @param array<string, mixed> $detail
	 */
	public static function createError(string $serviceName, array $detail, HealthCheckExceptionInterface $error): self
	{
		return new self($serviceName, false, $detail, $error);
	}

	public function getName(): string
	{
		return $this->serviceName;
	}

	public function isOk(): bool
	{
		return $this->ok;
	}

	public function getDetail(): array
	{
		return $this->detail;
	}

	public function getError(): ?HealthCheckExceptionInterface
	{
		return $this->error;
	}

	/**
	 * @return ServiceResultArray
	 */
	public function toArray(): array
	{
		return [
			'name' => $this->getName(),
			'is_ok' => $this->isOk(),
			'detail' => $this->getDetail(),
			'error' => $this->getError()?->getMessage(),
		];
	}

	/**
	 * @return ServiceResultJson
	 */
	public function jsonSerialize(): array
	{
		$array = $this->toArray();
		$array['detail'] = (object) $array['detail'];

		return $array;
	}
}
