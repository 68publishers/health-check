<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck;

final class StaticExportModeResolver implements ExportModeResolverInterface
{
	public function __construct(
		private readonly ExportMode $exportMode,
	) {
	}

	public function resolve(): ExportMode
	{
		return $this->exportMode;
	}
}
