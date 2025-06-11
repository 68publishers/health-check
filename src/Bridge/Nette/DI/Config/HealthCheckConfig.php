<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\Config;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Schema\DynamicParameter;
use Nette\DI\Definitions\Statement;
use SixtyEightPublishers\HealthCheck\ExportMode;
use function implode;
use function sprintf;
use function array_map;
use function is_string;
use function str_starts_with;

final class HealthCheckConfig
{
	public const ExportModeFullIfDebug = 'full_if_debug';

	/** @var array<Statement> */
	public array $service_checkers;

	public string|DynamicParameter $export_mode;

	public static function getSchema(): Schema
	{
		return Expect::structure([
			'service_checkers' => Expect::listOf(Expect::anyOf(Expect::string(), Expect::type(Statement::class)))
				->default([])
				->before(
					static fn (array $items): array => array_map(
						static fn ($item): Statement => $item instanceof Statement ? $item : new Statement($item),
						$items
					),
				),
			'export_mode' => Expect::anyOf(Expect::string())
				->dynamic()
				->default(self::ExportModeFullIfDebug)
				->assert(
					static fn (string|DynamicParameter $exportMode): bool => !is_string($exportMode) || null !== ExportMode::tryFrom($exportMode) || self::ExportModeFullIfDebug === $exportMode || str_starts_with($exportMode, '@'),
					sprintf(
						'Invalid export mode, allowed values are \'%s\', \'%s\', dynamic parameter or service reference.',
						implode(
							'\', \'',
							array_map(
								static fn (ExportMode $mode): string => $mode->value,
								ExportMode::cases()
							)
						),
						self::ExportModeFullIfDebug
					)
				),
		])->castTo(self::class);
	}
}
