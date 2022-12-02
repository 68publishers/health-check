<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck;

interface ExportModeResolverInterface
{
	public function resolve(): ExportMode;
}
