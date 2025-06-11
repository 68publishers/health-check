<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\Config;

use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class HealthCheckApplicationConfig
{
	public false|string $route;

	public static function getSchema(): Schema
	{
		return Expect::structure([
			'route' => Expect::anyOf(false, Expect::string())->default('/health-check'),
		])->castTo(self::class);
	}
}
