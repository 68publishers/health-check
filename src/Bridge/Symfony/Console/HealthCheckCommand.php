<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Symfony\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;

final class HealthCheckCommand extends Command
{
	private HealthCheckerInterface $healthChecker;

	/**
	 * @param \SixtyEightPublishers\HealthCheck\HealthCheckerInterface $healthChecker
	 */
	public function __construct(HealthCheckerInterface $healthChecker)
	{
		parent::__construct();

		$this->healthChecker = $healthChecker;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configure(): void
	{
		$this->setName('health-check')
			->setDescription('Checks statuses of the application services.')
			->addArgument('services', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Names of services that will be checked. All services are checked by default.', [])
			->addOption('full', NULL, InputOption::VALUE_NONE, 'Dumps a full export with all services.');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$result = $this->healthChecker->check(
			$input->getArgument('services'),
			$input->getOption('full') ? HealthCheckerInterface::ARRAY_EXPORT_MODE_FULL : HealthCheckerInterface::ARRAY_EXPORT_MODEL_SIMPLE
		);

		$output->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

		return 0;
	}
}
