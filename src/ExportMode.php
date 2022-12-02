<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck;

enum ExportMode: string
{
	case Simple = 'simple';
	case Full = 'full';
}
