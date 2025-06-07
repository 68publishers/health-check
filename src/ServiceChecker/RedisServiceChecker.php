<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use Redis;
use RedisException;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use function error_get_last;

final class RedisServiceChecker implements ServiceCheckerInterface
{
	/**
	 * @param string|array{0: string, 1: string}|null $auth
	 */
	public function __construct(
		private readonly string $host = '127.0.0.1',
		private readonly int|string $port = 6379,
		private readonly string|array|null $auth = null,
		private readonly string $serviceName = 'redis',
	) {
	}

	public function getName(): string
	{
		return $this->serviceName;
	}

	public function check(): ResultInterface
	{
		try {
			$redis = new Redis();

			if (false === @$redis->connect($this->host, (int) $this->port)) {
				$lastError = error_get_last();

				throw new RedisException(null !== $lastError ? $lastError['message'] : 'Can\'t connect to redis, unknown error.');
			}

			$authResult = $this->doAuth($redis);

			if (null !== $authResult) {
				return $authResult;
			}

			$redis->ping();
			$redis->close();

			return ServiceResult::createOk(
				serviceName: $this->getName(),
				detail: [],
			);
		} catch (RedisException $e) {
			return ServiceResult::createError(
				serviceName: $this->getName(),
				detail: [],
				error: new HealthCheckException(
					message: $e->getMessage(),
					code: 0,
					previous: $e,
				),
			);
		}
	}

	private function doAuth(Redis $redis): ?ResultInterface
	{
		if (null === $this->auth) {
			return null;
		}

		try {
			$authorized = $redis->auth($this->auth);
		} catch (RedisException $e) {
			$authorized = false;
		}

		return $authorized
			? null
			: ServiceResult::createError(
				serviceName: $this->getName(),
				detail: [],
				error: new HealthCheckException(
					message: 'Failed to auth a connection.',
					code: 0,
					previous: $e ?? null,
				)
			);
	}
}
