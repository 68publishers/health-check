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
	public function __construct(
		private readonly string $host = '127.0.0.1',
		private readonly int $port = 6379,
		private readonly ?string $auth = NULL,
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

			if (FALSE === @$redis->connect($this->host, $this->port)) {
				$lastError = error_get_last();

				throw new RedisException(NULL !== $lastError ? $lastError['message'] : 'Can\'t connect to redis, unknown error.');
			}

			if (!empty($this->auth) && FALSE === $redis->auth($this->auth)) {
				return ServiceResult::createError($this->getName(), 'unauthorized', new HealthCheckException('Failed to auth a connection.'));
			}

			$redis->ping();
			$redis->close();

			return ServiceResult::createOk($this->getName());
		} catch (RedisException $e) {
			return ServiceResult::createError($this->getName(), 'down', new HealthCheckException($e->getMessage(), $e->getCode(), $e));
		}
	}
}
