<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\Exception\MultipleResultsException;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;
use function array_map;
use function array_filter;

final class HealthCheckResult implements ResultInterface
{
	/** @var array<ResultInterface> */
	private array $results;

	private ExportMode $exportMode = ExportMode::Simple;

	/**
	 * @param array<ResultInterface> $results
	 */
	public function __construct(array $results = [])
	{
		$this->results = (static fn (ResultInterface ...$results): array => $results)(...$results);
	}

	public function withResult(ResultInterface $result): self
	{
		$me = clone $this;
		$me->results[] = $result;

		return $me;
	}

	public function withExportMode(ExportMode $exportMode): self
	{
		$me = clone $this;
		$me->exportMode = $exportMode;

		return $me;
	}

	/**
	 * @return array<ResultInterface>
	 */
	public function getResults(): array
	{
		return $this->results;
	}

	public function getExportMode(): ExportMode
	{
		return $this->exportMode;
	}

	public function getName(): string
	{
		return 'health_check';
	}

	public function isOk(): bool
	{
		foreach ($this->results as $result) {
			if (!$result->isOk()) {
				return false;
			}
		}

		return true;
	}

	public function getStatus(): string
	{
		foreach ($this->results as $result) {
			if (!$result->isOk()) {
				return 'failed';
			}
		}

		return 'ok';
	}

	public function getError(): ?HealthCheckExceptionInterface
	{
		$results = array_filter($this->results, static fn (ResultInterface $result): bool => !$result->isOk());

		return empty($results) ? null : new MultipleResultsException($results);
	}

	/**
	 * @return array{status: string, is_ok: bool, services?: array<int, array<string, mixed>>}
	 */
	public function toArray(): array
	{
		$array = [
			'status' => $this->getStatus(),
			'is_ok' => $this->isOk(),
		];

		if (ExportMode::Full === $this->exportMode) {
			$array['services'] = array_map(static fn (ResultInterface $result): array => $result->toArray(), $this->results);
		}

		return $array;
	}

	/**
	 * @return array{status: string, is_ok: bool, services?: array<int, array<string, mixed>>}
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
