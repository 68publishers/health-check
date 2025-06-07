<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\PingReceiver;

use Closure;
use SixtyEightPublishers\HealthCheck\Result\PingResult;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;

interface PingReceiverInterface
{
	/**
	 * @param Closure(): ResultInterface $closure
	 */
	public function pingUsing(Closure $closure): PingResult;
}
