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
		$result = ServiceResult::createOk('test', ['status_code' => 200]);

		Assert::same('test', $result->getName());
		Assert::same(['status_code' => 200], $result->getDetail());
		Assert::true($result->isOk());
		Assert::null($result->getError());
		Assert::same([
			'name' => 'test',
			'is_ok' => true,
			'detail' => ['status_code' => 200],
			'error' => null,
		], $result->toArray());
		Assert::same('{"name":"test","is_ok":true,"detail":{"status_code":200},"error":null}', json_encode($result, JSON_THROW_ON_ERROR));
	}

	public function testErrorServiceResultCreation(): void
	{
		$exception = new HealthCheckException('503 Service unavailable');
		$result = ServiceResult::createError('test', ['status_code' => 503], $exception);

		Assert::same('test', $result->getName());
		Assert::same(['status_code' => 503], $result->getDetail());
		Assert::false($result->isOk());
		Assert::same($exception, $result->getError());
		Assert::same([
			'name' => 'test',
			'is_ok' => false,
			'detail' => ['status_code' => 503],
			'error' => '503 Service unavailable',
		], $result->toArray());
		Assert::same('{"name":"test","is_ok":false,"detail":{"status_code":503},"error":"503 Service unavailable"}', json_encode($result, JSON_THROW_ON_ERROR));
	}
}

(new ServiceResultTest())->run();
