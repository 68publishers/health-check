<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Exception;

use SixtyEightPublishers\HealthCheck\Result\ResultInterface;

final class MultipleResultsException extends HealthCheckException
{
	/** @var \SixtyEightPublishers\HealthCheck\Result\ResultInterface[]  */
	private array $results;

	/**
	 * @param \SixtyEightPublishers\HealthCheck\Result\ResultInterface[] $results
	 */
	public function __construct(array $results)
	{
		$this->results = (static fn (ResultInterface ...$results): array => $results)(...$results);

		parent::__construct(
			"Some health checks failed: \n" . implode("\n", array_map(static fn (ResultInterface $result): string => sprintf('[%s]: %s', $result->getName(), NULL !== $result->getError() ? $result->getError()->getMessage() : '?'), $this->results)),
			0
		);
	}

	/**
	 * @return \SixtyEightPublishers\HealthCheck\Result\ResultInterface[]
	 */
	public function getResults(): array
	{
		return $this->results;
	}
}
