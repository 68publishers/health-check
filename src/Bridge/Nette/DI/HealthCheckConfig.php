<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI;

use Nette\Schema\DynamicParameter;
use Nette\DI\Definitions\Statement;

final class HealthCheckConfig
{
	/** @var array<Statement> */
	public array $service_checkers;

	public string|DynamicParameter $export_mode;
}
