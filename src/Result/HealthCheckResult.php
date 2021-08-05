<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use SixtyEightPublishers\HealthCheck\Exception\InvalidArgumentException;
use SixtyEightPublishers\HealthCheck\Exception\MultipleResultsException;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;

final class HealthCheckResult implements ResultInterface
{
	public const ARRAY_EXPORT_MODEL_SIMPLE = 'simple';
	public const ARRAY_EXPORT_MODE_FULL = 'full';

	private const ARRAY_EXPORT_MODES = [
		self::ARRAY_EXPORT_MODEL_SIMPLE,
		self::ARRAY_EXPORT_MODE_FULL,
	];

	/** @var \SixtyEightPublishers\HealthCheck\Result\ResultInterface[]  */
	private array $results;

	private string $arrayExportMode;

	/**
	 * @param \SixtyEightPublishers\HealthCheck\Result\ResultInterface[] $results
	 * @param string                                                     $arrayExportMode
	 */
	public function __construct(array $results = [], string $arrayExportMode = self::ARRAY_EXPORT_MODEL_SIMPLE)
	{
		$this->results = $results;
		$this->setArrayExportMode($arrayExportMode);
	}

	/**
	 * @param \SixtyEightPublishers\HealthCheck\Result\ResultInterface $result
	 *
	 * @return \SixtyEightPublishers\HealthCheck\Result\HealthCheckResult
	 */
	public function withResult(ResultInterface $result): self
	{
		$results = $this->results;
		$results[] = $result;

		return new self($results, $this->arrayExportMode);
	}

	/**
	 * @return \SixtyEightPublishers\HealthCheck\Result\ResultInterface[]
	 */
	public function getResults(): array
	{
		return $this->results;
	}

	/**
	 * @param string $arrayExportMode
	 *
	 * @return \SixtyEightPublishers\HealthCheck\Result\HealthCheckResult
	 */
	public function setArrayExportMode(string $arrayExportMode): self
	{
		if (!in_array($arrayExportMode, self::ARRAY_EXPORT_MODES, TRUE)) {
			throw new InvalidArgumentException(sprintf(
				'An array export mode "%s" is not supported.',
				$arrayExportMode
			));
		}

		$this->arrayExportMode = $arrayExportMode;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return 'health_check';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isOk(): bool
	{
		foreach ($this->results as $result) {
			if (!$result->isOk()) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getStatus(): string
	{
		foreach ($this->results as $result) {
			if (!$result->isOk()) {
				return 'failed';
			}
		}

		return 'ok';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getError(): ?HealthCheckExceptionInterface
	{
		$results = array_filter($this->results, static fn (ResultInterface $result): bool => !$result->isOk());

		return empty($results) ? NULL : new MultipleResultsException($results);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		$array = [
			'status' => $this->getStatus(),
			'is_ok' => $this->isOk(),
		];

		if (self::ARRAY_EXPORT_MODE_FULL === $this->arrayExportMode) {
			$array['services'] = array_map(static fn (ResultInterface $result): array => $result->toArray(), $this->results);
		}

		return $array;
	}

	/**
	 * {@inheritDoc}
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
