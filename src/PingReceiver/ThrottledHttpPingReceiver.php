<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\PingReceiver;

use Closure;
use Throwable;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use SixtyEightPublishers\HealthCheck\Result\PingResult;
use function time;
use function is_int;

final class ThrottledHttpPingReceiver implements PingReceiverInterface
{
	private readonly Cache $cache;

	private readonly HttpPingReceiver $inner;

	/**
	 * @param list<string>         $headers
	 * @param array<string, mixed> $extra
	 */
	public function __construct(
		readonly Storage        $storage,
		private readonly int    $throttleTtl,
		private readonly string $cacheKey,
		string                  $url,
		string                  $method = 'POST',
		array                   $headers = [],
		int                     $timeout = 5,
		string $cacheNamespace = 'ThrottledHttpPingReceiver',
		array $extra = [],
	) {
		$this->cache = new Cache(
			storage: $this->storage,
			namespace: $cacheNamespace,
		);
		$this->inner = new HttpPingReceiver(
			url: $url,
			method: $method,
			headers: $headers,
			timeout: $timeout,
			extra: $extra,
		);
	}

	public function pingUsing(Closure $closure): PingResult
	{
		try {
			$now = time();
			$lastPing = $this->cache->load($this->cacheKey);

			if (is_int($lastPing) && ($now - $lastPing) < $this->throttleTtl) {
				return new PingResult(
					ok: true,
				);
			}

			$result = null;

			$this->cache->save(
				key: $this->cacheKey,
				data: function (&$dependencies) use ($closure, &$result, $now): int {
					$result = $this->inner->pingUsing(closure: $closure);
					$dependencies[Cache::Expire] = $this->throttleTtl;

					return $now;
				}
			);

			if (null === $result) {
				return new PingResult(
					ok: true,
				);
			}

			return $result;
		} catch (Throwable $e) {
			return new PingResult(
				ok: false,
			);
		}
	}
}
