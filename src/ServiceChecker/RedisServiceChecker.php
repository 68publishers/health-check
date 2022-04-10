<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use Redis;
use RedisException;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;

final class RedisServiceChecker implements ServiceCheckerInterface
{
	protected string $host;

	protected int $port;

	private ?string $auth;

	private string $serviceName;

	/**
	 * @param string      $host
	 * @param int         $port
	 * @param string|NULL $auth
	 * @param string      $serviceName
	 */
	public function __construct(string $host = '127.0.0.1', int $port = 6379, ?string $auth = NULL, string $serviceName = 'redis')
	{
		$this->host = $host;
		$this->port = $port;
		$this->auth = $auth;
		$this->serviceName = $serviceName;
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
			$redis = new Redis();

			if (FALSE === @$redis->connect($this->host, $this->port)) {
				throw new RedisException(error_get_last());
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
