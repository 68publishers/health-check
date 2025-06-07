<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Fixtures;

use Closure;
use SixtyEightPublishers\HealthCheck\Result\PingResult;
use SixtyEightPublishers\HealthCheck\PingReceiver\PingReceiverInterface;

final class CatchPingReceiver implements PingReceiverInterface
{
	public mixed $data = null;

	public function pingUsing(Closure $closure): PingResult
	{
		$this->data = $closure();

		return new PingResult(
			ok: true,
		);
	}
}
