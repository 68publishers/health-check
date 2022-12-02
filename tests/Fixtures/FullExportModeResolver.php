<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Fixtures;

use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\ExportModeResolverInterface;

final class FullExportModeResolver implements ExportModeResolverInterface
{
	public function resolve(): ExportMode
	{
		return ExportMode::Full;
	}
}
