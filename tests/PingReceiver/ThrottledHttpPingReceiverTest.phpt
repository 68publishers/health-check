<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Result;

use Tester\Assert;
use Tester\TestCase;
use Nette\Caching\Storages\MemoryStorage;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\PingReceiver\ThrottledHttpPingReceiver;

require __DIR__ . '/../bootstrap.php';

final class ThrottledHttpPingReceiverTest extends TestCase
{
	public function testPingShouldBeReceived(): void
	{
		$receiver = new ThrottledHttpPingReceiver(
			storage: new MemoryStorage(),
			throttleTtl: 3,
			cacheKey: 'test',
			url: $_ENV['WEB_SERVICE_HOST'] . '/ping-receiver',
		);

		$closureCalled = false;
		$closure = function () use (&$closureCalled): ResultInterface {
			$closureCalled = true;

			return ServiceResult::createOk('test', []);
		};

		$result = $receiver->pingUsing(closure: $closure);

		Assert::true($closureCalled);

		Assert::true($result->isOk());
		Assert::same('ping', $result->getName());
		Assert::same([], $result->getDetail());
		Assert::null($result->getError());
		Assert::equal(['is_ok' => true], $result->toArray());
		Assert::same('{"is_ok":true}', json_encode($result));
	}

	public function testPingShouldBeThrottled(): void
	{
		$receiver = new ThrottledHttpPingReceiver(
			storage: new MemoryStorage(),
			throttleTtl: 3,
			cacheKey: 'test',
			url: $_ENV['WEB_SERVICE_HOST'] . '/ping-receiver',
		);

		$called = 0;
		$closure = function () use (&$called): ResultInterface {
			$called += 1;

			return ServiceResult::createOk('test', []);
		};

		$result = $receiver->pingUsing(closure: $closure);
		Assert::true($result->isOk());

		$result = $receiver->pingUsing(closure: $closure);
		Assert::true($result->isOk());
		Assert::same(1, $called);

		sleep(1);

		$result = $receiver->pingUsing(closure: $closure);
		Assert::true($result->isOk());
		Assert::same(1, $called);

		sleep(2);

		$result = $receiver->pingUsing(closure: $closure);
		Assert::true($result->isOk());

		$result = $receiver->pingUsing(closure: $closure);
		Assert::true($result->isOk());
		Assert::same(2, $called);
	}
}

(new ThrottledHttpPingReceiverTest())->run();
