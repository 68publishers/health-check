<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Exception;

use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use function implode;
use function sprintf;
use function array_map;

final class MultipleResultsException extends HealthCheckException
{
	/** @var array<ResultInterface> */
	private readonly array $results;

	/**
	 * @param array<ResultInterface> $results
	 */
	public function __construct(array $results)
	{
		$this->results = (static fn (ResultInterface ...$results): array => $results)(...$results);
		$message = implode(
			"\n",
			array_map(
				static fn (ResultInterface $result): string => sprintf(
					'[%s]: %s',
					$result->getName(),
					NULL !== $result->getError() ? $result->getError()->getMessage() : '?'
				),
				$this->results
			)
		);

		parent::__construct("Some health checks failed" . ('' === $message ? '.' : (":\n" . $message)));
	}

	/**
	 * @return array<ResultInterface>
	 */
	public function getResults(): array
	{
		return $this->results;
	}
}
