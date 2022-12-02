<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Symfony\Console\Command;

use JsonException;
use Symfony\Component\Console\Command\Command;
use SixtyEightPublishers\HealthCheck\ExportMode;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use function count;
use function implode;
use function sprintf;
use function array_map;
use function json_encode;
use function array_filter;

final class HealthCheckCommand extends Command
{
	protected static $defaultName = 'health-check';

	public function __construct(
		private readonly HealthCheckerInterface $healthChecker,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setDescription('Checks statuses of the application services.')
			->addArgument('services', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Names of services that will be checked. All services are checked by default.')
			->addOption('export-mode', NULL, InputOption::VALUE_REQUIRED, 'Overwrite the default export mode. Allowed values are "simple" and "full"');
	}

	/**
	 * @throws JsonException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);
		$servicesOnly = $input->getArgument('services');
		$exportMode = $input->getOption('export-mode');

		$result = $this->healthChecker->check(
			is_array($servicesOnly) && !empty($servicesOnly) ? $servicesOnly : NULL,
			is_string($exportMode) ? ExportMode::from($exportMode) : NULL
		);

		$style->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

		if ($result->isOk()) {
			$style->success('All services are healthy.');
		} else {
			$failedServices = array_filter(
				$result instanceof HealthCheckResult ? $result->getResults() : [],
				static fn (ResultInterface $serviceResult): bool => !$serviceResult->isOk()
			);
			$failedServiceNames = implode(
				'", "',
				array_map(
					static fn (ResultInterface $serviceResult): string => $serviceResult->getName(),
					$failedServices
				)
			);

			$style->warning(sprintf(
				'Service%s%s %s unhealthy.',
				1 === count($failedServices) ? '' : 's',
				'' === $failedServiceNames ? '' : (' "' . $failedServiceNames . '"'),
				1 === count($failedServices) ? 'is' : 'are'
			));
		}

		return Command::SUCCESS;
	}
}
