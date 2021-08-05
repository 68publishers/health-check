<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\ServiceChecker;

use PDO;
use PDOException;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;
use SixtyEightPublishers\HealthCheck\Exception\InvalidArgumentException;

final class PDOServiceChecker implements ServiceCheckerInterface
{
	private string $dsn;

	private ?string $user;

	private ?string $password;

	private array $options;

	private string $serviceName;

	/**
	 * @param string      $dsn
	 * @param string|NULL $user
	 * @param string|NULL $password
	 * @param array       $options
	 * @param string      $serviceName
	 */
	public function __construct(string $dsn, ?string $user = NULL, ?string $password = NULL, array $options = [], string $serviceName = 'database')
	{
		$this->dsn = $dsn;
		$this->user = $user;
		$this->password = $password;
		$this->options = $options;
		$this->serviceName = $serviceName;
	}

	/**
	 * @param array $params
	 *
	 * @return \SixtyEightPublishers\HealthCheck\ServiceChecker\PDOServiceChecker
	 * @throws \SixtyEightPublishers\HealthCheck\Exception\InvalidArgumentException
	 */
	public static function fromParams(array $params): self
	{
		foreach (['driver', 'host', 'port', 'dbname'] as $key) {
			if (!isset($params[$key])) {
				throw new InvalidArgumentException(sprintf(
					'Missing required parameter "%s".',
					$key
				));
			}
		}

		return new self(sprintf(
			'%s:host=%s;port=%s;dbname=%s;',
			$params['driver'],
			$params['host'],
			$params['port'],
			$params['dbname']
		), $params['user'] ?? NULL, $params['password'] ?? NULL, $params['options'] ?? []);
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
			$pdo = new PDO($this->dsn, $this->user, $this->password, $this->options);

			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->query('SELECT 1;')->execute();

			return ServiceResult::createOk($this->getName());
		} catch (PDOException $e) {
			return ServiceResult::createError($this->getName(), 'down', new HealthCheckException($e->getMessage(), $e->getCode(), $e));
		}
	}
}
