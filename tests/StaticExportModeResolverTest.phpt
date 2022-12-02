<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests;

use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\StaticExportModeResolver;

require __DIR__ . '/bootstrap.php';

final class StaticExportModeResolverTest extends TestCase
{
	public function testResolveFullExportMode(): void
	{
		$resolver = new StaticExportModeResolver(ExportMode::Full);

		Assert::same(ExportMode::Full, $resolver->resolve());
	}

	public function testResolveSimpleExportMode(): void
	{
		$resolver = new StaticExportModeResolver(ExportMode::Simple);

		Assert::same(ExportMode::Simple, $resolver->resolve());
	}
}

(new StaticExportModeResolverTest())->run();
