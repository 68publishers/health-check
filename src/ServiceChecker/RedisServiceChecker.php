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
		private readonly string|array|NULL $auth = NULL,
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

			if (FALSE === @$redis->connect($this->host, (int) $this->port)) {
				$lastError = error_get_last();

				throw new RedisException(NULL !== $lastError ? $lastError['message'] : 'Can\'t connect to redis, unknown error.');
			}

			$authResult = $this->doAuth($redis);

			if (NULL !== $authResult) {
				return $authResult;
			}

			$redis->ping();
			$redis->close();

			return ServiceResult::createOk($this->getName());
		} catch (RedisException $e) {
			return ServiceResult::createError($this->getName(), 'down', new HealthCheckException($e->getMessage(), 0, $e));
		}
	}

	private function doAuth(Redis $redis): ?ResultInterface
	{
		if (NULL === $this->auth) {
			return NULL;
		}

		try {
			$authorized = $redis->auth($this->auth);
		} catch (RedisException $e) {
			$authorized = FALSE;
		}

		return $authorized
			? NULL
			: ServiceResult::createError($this->getName(), 'unauthorized', new HealthCheckException('Failed to auth a connection.', 0, $e ?? NULL));
	}
}
