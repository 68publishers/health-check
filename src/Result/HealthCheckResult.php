<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Result;

use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\Exception\MultipleResultsException;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckExceptionInterface;
use function array_map;
use function array_filter;
use function array_values;

/**
 * @phpstan-import-type ServiceResultArray from ServiceResult
 * @phpstan-import-type ServiceResultJson from ServiceResult
 *
 * @phpstan-type HealthCheckResultArray = array{
 *     is_ok: bool,
 *     detail?: array{
 *         services: list<ServiceResultArray>,
 *     },
 * }
 * @phpstan-type HealthCheckResultJson = array{
 *     is_ok: bool,
 *     detail?: array{
 *         services: list<ServiceResultJson>,
 *     },
 * }
 */
final class HealthCheckResult implements ResultInterface
{
	/** @var list<ResultInterface> */
	private array $results;

	private ExportMode $exportMode = ExportMode::Simple;

	/**
	 * @param list<ResultInterface> $results
	 */
	public function __construct(array $results = [])
	{
		$this->results = (static fn (ResultInterface ...$results): array => array_values($results))(...$results);
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
	 * @return list<ResultInterface>
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

	/**
	 * @return array{
	 *     services: list<ServiceResultArray>,
	 * }
	 */
	public function getDetail(): array
	{
		return [ # @phpstan-ignore-line
			'services' => array_map(static fn (ResultInterface $result): array => $result->toArray(), $this->results),
		];
	}

	public function getError(): ?HealthCheckExceptionInterface
	{
		$results = array_filter($this->results, static fn (ResultInterface $result): bool => !$result->isOk());

		return empty($results) ? null : new MultipleResultsException($results);
	}

	/**
	 * @return HealthCheckResultArray
	 */
	public function toArray(): array
	{
		$array = [
			'is_ok' => $this->isOk(),
		];

		if (ExportMode::Full === $this->exportMode) {
			$array['detail'] = $this->getDetail();
		}

		return $array;
	}

	/**
	 * @return HealthCheckResultJson
	 */
	public function jsonSerialize(): array
	{
		$array = [
			'is_ok' => $this->isOk(),
		];

		if (ExportMode::Full === $this->exportMode) {
			$array['detail'] = [
				'services' => array_map(static fn (ResultInterface $result): array => (array) $result->jsonSerialize(), $this->results),
			];
		}

		return $array; # @phpstan-ignore-line
	}
}
