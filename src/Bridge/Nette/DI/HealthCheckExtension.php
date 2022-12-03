<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\DI;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\DI\CompilerExtension;
use Nette\Schema\DynamicParameter;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\HealthChecker;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;
use SixtyEightPublishers\HealthCheck\StaticExportModeResolver;
use SixtyEightPublishers\HealthCheck\ExportModeResolverInterface;
use SixtyEightPublishers\HealthCheck\ServiceChecker\ServiceCheckerInterface;
use function assert;
use function implode;
use function sprintf;
use function array_map;
use function is_string;
use function str_starts_with;

final class HealthCheckExtension extends CompilerExtension
{
	private const EXPORT_MODE_FULL_IF_DEBUG = 'full_if_debug';

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'service_checkers' => Expect::listOf(Expect::anyOf(Expect::string(), Expect::type(Statement::class)))
				->default([])
				->before(
					static fn (array $items): array =>
					array_map(
						static fn ($item): Statement => $item instanceof Statement ? $item : new Statement($item),
						$items
					)
				),
			'export_mode' => Expect::anyOf(Expect::string())
				->dynamic()
				->default(self::EXPORT_MODE_FULL_IF_DEBUG)
				->assert(
					static fn (string|DynamicParameter $exportMode): bool => !is_string($exportMode) || null !== ExportMode::tryFrom($exportMode) || self::EXPORT_MODE_FULL_IF_DEBUG === $exportMode || str_starts_with($exportMode, '@'),
					sprintf(
						'Invalid export mode, allowed values are \'%s\', \'%s\', dynamic parameter or service reference.',
						implode(
							'\', \'',
							array_map(
								static fn (ExportMode $mode): string => $mode->value,
								ExportMode::cases()
							)
						),
						self::EXPORT_MODE_FULL_IF_DEBUG
					)
				),
		])->castTo(HealthCheckConfig::class);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();
		assert($config instanceof HealthCheckConfig);

		$builder->addDefinition($this->prefix('export_mode_resolver'))
			->setAutowired(false)
			->setType(ExportModeResolverInterface::class)
			->setFactory($this->createExportModeResolverStatement($config->export_mode));

		$healthChecker = $builder->addDefinition($this->prefix('health_checker'))
			->setType(HealthCheckerInterface::class)
			->setFactory(HealthChecker::class, [
				'exportModeResolver' => new Reference($this->prefix('export_mode_resolver')),
			]);

		foreach ($config->service_checkers as $i => $serviceCheckerFactory) {
			$serviceCheckerName = $this->prefix('service_checker.' . $i);

			$builder->addDefinition($serviceCheckerName)
				->setAutowired(false)
				->setType(ServiceCheckerInterface::class)
				->setFactory($serviceCheckerFactory);

			$healthChecker->addSetup('addServiceChecker', [
				new Reference($serviceCheckerName),
			]);
		}
	}

	private function createExportModeResolverStatement(string|DynamicParameter $exportMode): Statement
	{
		# return directly if statement
		if ($exportMode instanceof Statement) {
			return $exportMode;
		}

		# full-if-debug
		if (self::EXPORT_MODE_FULL_IF_DEBUG === $exportMode) {
			$debugMode = (bool) ($this->getContainerBuilder()->parameters['debugMode'] ?? false);

			return new Statement(StaticExportModeResolver::class, [
				new Statement([ExportMode::class, 'from'], [
					$debugMode ? ExportMode::Full->value : ExportMode::Simple->value,
				]),
			]);
		}

		# reference or classname/factory without ()
		if (is_string($exportMode) && null === ExportMode::tryFrom($exportMode)) {
			return new Statement($exportMode);
		}

		# one of ExportMode::cases() or dynamic parameter
		return new Statement(StaticExportModeResolver::class, [
			new Statement([ExportMode::class, 'from'], [
				$exportMode,
			]),
		]);
	}
}
