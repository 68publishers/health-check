<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use Throwable;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;

final class HttpServiceChecker implements ServiceCheckerInterface
{
	private string $serviceName;

	private string $url;

	private int $timeout;

	/**
	 * @param string $serviceName
	 * @param string $url
	 * @param int    $timeout
	 */
	public function __construct(string $serviceName, string $url, int $timeout = 5)
	{
		$this->serviceName = $serviceName;
		$this->url = $url;
		$this->timeout = $timeout;
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
	public function check(): ResultInterface
	{
		try {
			$context = stream_context_create([
				'http' => [
					'ignore_errors' => TRUE,
					'method' => 'GET',
					'timeout' => $this->timeout,
				],
			]);
			$headers = @get_headers($this->url, 0, $context);

			if (FALSE === $headers) {
				return $this->serviceIsDown('Can\'t fetch response.');
			}

			if (isset($headers[0]) && FALSE !== (bool) preg_match('~^HTTP\/[\d]\.[\d] 200 OK~', $headers[0])) {
				return ServiceResult::createOk($this->getName());
			}

			return $this->serviceIsDown(sprintf('Server respond with status header %s', $headers[0] ?? '[unknown]'));
		} catch (Throwable $e) {
			return $this->serviceIsDown(sprintf('[%s] %s', get_class($e), $e->getMessage()), $e);
		}
	}

	/**
	 * @param string          $message
	 * @param \Throwable|NULL $previous
	 *
	 * @return \SixtyEightPublishers\HealthCheck\Result\ResultInterface
	 */
	private function serviceIsDown(string $message, ?Throwable $previous = NULL): ResultInterface
	{
		return ServiceResult::createError(
			$this->getName(),
			'down',
			new HealthCheckException($message, NULL !== $previous ? $previous->getCode() : 0, $previous ?? NULL)
		);
	}
}
