<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Result;

use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Exception\HealthCheckException;

require __DIR__ . '/../bootstrap.php';

final class ServiceResultTest extends TestCase
{
	public function testOkServiceResultCreation(): void
	{
		$result = ServiceResult::createOk('test', 'ready');

		Assert::same('test', $result->getName());
		Assert::same('ready', $result->getStatus());
		Assert::true($result->isOk());
		Assert::null($result->getError());
		Assert::same([
			'name' => 'test',
			'is_ok' => TRUE,
			'status' => 'ready',
			'error' => NULL,
		], $result->toArray());
		Assert::same('{"name":"test","is_ok":true,"status":"ready","error":null}', json_encode($result, JSON_THROW_ON_ERROR));
	}

	public function testErrorServiceResultCreation(): void
	{
		$exception = new HealthCheckException('503 Service unavailable');
		$result = ServiceResult::createError('test', 'service unavailable', $exception);

		Assert::same('test', $result->getName());
		Assert::same('service unavailable', $result->getStatus());
		Assert::false($result->isOk());
		Assert::same($exception, $result->getError());
		Assert::same([
			'name' => 'test',
			'is_ok' => FALSE,
			'status' => 'service unavailable',
			'error' => '503 Service unavailable',
		], $result->toArray());
		Assert::same('{"name":"test","is_ok":false,"status":"service unavailable","error":"503 Service unavailable"}', json_encode($result, JSON_THROW_ON_ERROR));
	}
}

(new ServiceResultTest())->run();
