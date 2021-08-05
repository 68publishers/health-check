<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

class ServiceResult implements ResultInterface
{
	private string $serviceName;

	private bool $ok;

	private string $status;

	private ?HealthCheckExceptionInterface $error;

	/**
	 * @param string                                                                         $serviceName
	 * @param bool                                                                           $ok
	 * @param string                                                                         $status
	 * @param \SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface|NULL $error
	 */
	public function __construct(string $serviceName, bool $ok, string $status, ?HealthCheckExceptionInterface $error = NULL)
	{
		$this->serviceName = $serviceName;
		$this->ok = $ok;
		$this->status = $status;
		$this->error = $error;
	}

	/**
	 * @param string $serviceName
	 * @param string $status
	 *
	 * @return \SixtyEightPublishers\HealthCheck\Result\ServiceResult
	 */
	public static function createOk(string $serviceName, string $status = 'running'): self
	{
		return new static($serviceName, TRUE, $status);
	}

	/**
	 * @param string                                                                    $serviceName
	 * @param string                                                                    $status
	 * @param \SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface $error
	 *
	 * @return \SixtyEightPublishers\HealthCheck\Result\ServiceResult
	 */
	public static function createError(string $serviceName, string $status, HealthCheckExceptionInterface $error): self
	{
		return new static($serviceName, FALSE, $status, $error);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return $this->serviceName;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isOk(): bool
	{
		return $this->ok;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getStatus(): string
	{
		return $this->status;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getError(): ?HealthCheckExceptionInterface
	{
		return $this->error;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'name' => $this->getName(),
			'is_ok' => $this->isOk(),
			'status' => $this->getStatus(),
			'error' => NULL !== $this->getError() ? $this->getError()->getMessage() : NULL,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
