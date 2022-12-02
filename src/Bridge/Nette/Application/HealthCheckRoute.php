<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\Application;

use Nette\Application\Routers\Route;

final class HealthCheckRoute extends Route
{
	public function __construct(string $path = '/health-check')
	{
		parent::__construct($path, [
			'module' => 'HealthCheck',
			'presenter' => 'HealthCheck',
			'action' => 'default',
		]);
	}
}
