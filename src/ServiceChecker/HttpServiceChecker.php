<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use Throwable;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use function sprintf;
use function get_class;
use function preg_match;
use function get_headers;
use function stream_context_create;

final class HttpServiceChecker implements ServiceCheckerInterface
{
	public function __construct(
		private readonly string $serviceName,
		private readonly string $url,
		private readonly int $timeout = 5,
	) {
	}

	public function getName(): string
	{
		return $this->serviceName;
	}

	public function check(): ResultInterface
	{
		try {
			$context = stream_context_create([
				'http' => [
					'ignore_errors' => true,
					'method' => 'GET',
					'timeout' => $this->timeout,
				],
			]);
			$headers = @get_headers($this->url, false, $context);

			if (false === $headers) {
				return $this->serviceIsDown('Can\'t fetch a response.');
			}

			if (isset($headers[0]) && false !== (bool) preg_match('~^HTTP/\d\.\d 200 OK~', $headers[0])) {
				return ServiceResult::createOk($this->getName());
			}

			return $this->serviceIsDown(sprintf(
				'Server respond with the status header %s',
				$headers[0] ?? '[unknown]'
			));
		} catch (Throwable $e) {
			return $this->serviceIsDown(sprintf(
				'[%s] %s',
				get_class($e),
				$e->getMessage()
			), $e);
		}
	}

	private function serviceIsDown(string $message, ?Throwable $previous = null): ResultInterface
	{
		return ServiceResult::createError(
			$this->getName(),
			'down',
			new HealthCheckException($message, null !== $previous ? $previous->getCode() : 0, $previous ?? null)
		);
	}
}
