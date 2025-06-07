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
		private readonly string $method = 'GET',
		private readonly int $expectedCode = 200,
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
					'method' => $this->method,
					'timeout' => $this->timeout,
				],
			]);
			$headers = @get_headers(
				url: $this->url,
				context: $context,
			);

			if (false === $headers) {
				return $this->serviceIsDown(
					message: 'Can\'t fetch a response.',
					detail: [
						'status_code' => null,
					],
				);
			}

			if (preg_match('#^HTTP/\d+\.\d+\s+(\d{3})#', $headers[0] ?? '', $matches)) {
				$statusCode = (int) $matches[1];

				if ($statusCode === $this->expectedCode) {
					return ServiceResult::createOk(
						serviceName: $this->getName(),
						detail: [
							'status_code' => $statusCode,
						],
					);
				}

				return $this->serviceIsDown(
					message: sprintf(
						'Server respond with unexpected status code %d.',
						$statusCode,
					),
					detail: [
						'status_code' => $statusCode,
					],
				);
			}

			return $this->serviceIsDown(
				message: 'Server respond with unknown status code.',
				detail: [
					'status_code' => null,
				],
			);
		} catch (Throwable $e) {
			return $this->serviceIsDown(
				message: sprintf(
					'[%s] %s',
					get_class($e),
					$e->getMessage()
				),
				detail: [
					'status_code' => null,
				],
				previous: $e,
			);
		}
	}

	/**
	 * @param array<string, mixed> $detail
	 */
	private function serviceIsDown(string $message, array $detail = [], ?Throwable $previous = null): ResultInterface
	{
		return ServiceResult::createError(
			serviceName: $this->getName(),
			detail: $detail,
			error: new HealthCheckException(
				message: $message,
				code: null !== $previous ? $previous->getCode() : 0,
				previous: $previous ?? null,
			),
		);
	}
}
