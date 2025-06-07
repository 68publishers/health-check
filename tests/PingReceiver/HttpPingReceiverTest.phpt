<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Result;

use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\Result\ServiceResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\PingReceiver\HttpPingReceiver;

require __DIR__ . '/../bootstrap.php';

final class HttpPingReceiverTest extends TestCase
{
	public function testPingShouldBeReceived(): void
	{
		$receiver = new HttpPingReceiver(
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
}

(new HttpPingReceiverTest())->run();
