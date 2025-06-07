<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\PingReceiver;

use Closure;
use Throwable;
use SixtyEightPublishers\HealthCheck\Result\PingResult;
use function mb_strlen;
use function get_headers;
use function json_encode;
use function base64_decode;
use function str_starts_with;
use function stream_context_create;

final class HttpPingReceiver implements PingReceiverInterface
{
	/**
	 * @param list<string>         $headers
	 * @param array<string, mixed> $extra
	 */
	public function __construct(
		private readonly string $url,
		private readonly string $method = 'POST',
		private readonly array $headers = [],
		private readonly int $timeout = 5,
		private readonly array $extra = [],
	) {
	}

	public function pingUsing(Closure $closure): PingResult
	{
		try {
			$data = json_encode(value: [
				'health_check' => [
					'result' => $closure(),
					'extra' => (object) $this->extra,
				],
			], flags: JSON_THROW_ON_ERROR);

			$headers = $this->headers;
			$headers[] = 'Content-Type: application/json';
			$headers[] = 'Content-Length: ' . mb_strlen($data);

			$context = stream_context_create([
				'http' => [
					'ignore_errors' => true,
					'method' => $this->method,
					'timeout' => $this->timeout,
					'header' => $headers,
					'content' => $data,
				],
			]);

			$responseHeaders = @get_headers(
				url: str_starts_with($this->url, 'http://') || str_starts_with($this->url, 'https://') ? $this->url : base64_decode($this->url),
				context: $context,
			) ?: [];

			$statusCode = null;

			if (preg_match('#^HTTP/\d+\.\d+\s+(\d{3})#', $responseHeaders[0] ?? '', $matches)) {
				$statusCode = (int) $matches[1];
			}

			return new PingResult(
				ok: null !== $statusCode && $statusCode >= 200 && $statusCode <= 299,
			);
		} catch (Throwable $e) {
			return new PingResult(
				ok: false,
			);
		}
	}
}
