<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Bridge\Symfony\Console\Command;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use SixtyEightPublishers\HealthCheck\ExportMode;
use Symfony\Component\Console\Tester\CommandTester;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;
use SixtyEightPublishers\HealthCheck\Result\ResultInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\Bridge\Symfony\Console\Command\HealthCheckCommand;

require __DIR__ . '/../../../../bootstrap.php';

final class HealthCheckCommandTest extends TestCase
{
	public function testSuccessfulResult(): void
	{
		$this->assertRunWithSuccessfulResult(NULL, NULL);
	}

	public function testSuccessfulResultWithServicesOnlyArgument(): void
	{
		$this->assertRunWithSuccessfulResult(['first service'], NULL);
	}

	public function testSuccessfulResultWithExportModeOption(): void
	{
		$this->assertRunWithSuccessfulResult(NULL, ExportMode::Full);
	}

	public function testFailedResultWithoutServiceCheckers(): void
	{
		$this->assertRunWithFailedResult([], 'Services are unhealthy.', NULL, NULL);
	}

	public function testSingleFailedResult(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

		$secondServiceResult->shouldReceive('getName')
			->once()
			->andReturn('second service');

		$this->assertRunWithFailedResult([$firstServiceResult, $secondServiceResult], 'Service "second service" is unhealthy.', NULL, NULL);
	}

	public function testMultipleFailedResult(): void
	{
		$firstServiceResult = Mockery::mock(ResultInterface::class);
		$secondServiceResult = Mockery::mock(ResultInterface::class);

		$firstServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

		$firstServiceResult->shouldReceive('getName')
			->once()
			->andReturn('first service');

		$secondServiceResult->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

		$secondServiceResult->shouldReceive('getName')
			->once()
			->andReturn('second service');

		$this->assertRunWithFailedResult([$firstServiceResult, $secondServiceResult], 'Services "first service", "second service" are unhealthy.', NULL, NULL);
	}

	public function testFailedResultWithServicesOnlyArgument(): void
	{
		$serviceResult = Mockery::mock(ResultInterface::class);

		$serviceResult->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

		$serviceResult->shouldReceive('getName')
			->once()
			->andReturn('second service');

		$this->assertRunWithFailedResult([$serviceResult], 'Service "second service" is unhealthy.', ['second service'], NULL);
	}

	public function testFailedResultWithExportModeOption(): void
	{
		$serviceResult = Mockery::mock(ResultInterface::class);

		$serviceResult->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

		$serviceResult->shouldReceive('getName')
			->once()
			->andReturn('second service');

		$this->assertRunWithFailedResult([$serviceResult], 'Service "second service" is unhealthy.', NULL, ExportMode::Full);
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}

	private function assertRunWithSuccessfulResult(?array $servicesOnly, ?ExportMode $exportMode): void
	{
		$checker = Mockery::mock(HealthCheckerInterface::class);
		$result = Mockery::mock(HealthCheckResult::class);

		$result->shouldReceive('isOk')
			->once()
			->andReturn(TRUE);

		$result->shouldReceive('jsonSerialize')
			->once()
			->andReturn(['status' => 'ok', 'is_ok' => TRUE]);

		$checker->shouldReceive('check')
			->once()
			->with($servicesOnly, $exportMode)
			->andReturn($result);

		$tester = $this->executeCommand($checker, $servicesOnly, $exportMode);
		Assert::same(Command::SUCCESS, $tester->getStatusCode());

		$display = $tester->getDisplay();
		$json = <<<JSON
{
    "status": "ok",
    "is_ok": true
}
JSON;

		Assert::contains($json, $display);
		Assert::contains('All services are healthy.', $display);
	}

	private function assertRunWithFailedResult(array $serviceCheckerResults, string $message, ?array $servicesOnly, ?ExportMode $exportMode): void
	{
		$checker = Mockery::mock(HealthCheckerInterface::class);
		$result = Mockery::mock(HealthCheckResult::class);

		$result->shouldReceive('isOk')
			->once()
			->andReturn(FALSE);

		$result->shouldReceive('jsonSerialize')
			->once()
			->andReturn(['status' => 'failed', 'is_ok' => FALSE]);

		$result->shouldReceive('getResults')
			->once()
			->andReturn($serviceCheckerResults);

		$checker->shouldReceive('check')
			->once()
			->with($servicesOnly, $exportMode)
			->andReturn($result);

		$tester = $this->executeCommand($checker, $servicesOnly, $exportMode);
		Assert::same(Command::SUCCESS, $tester->getStatusCode());

		$display = $tester->getDisplay();
		$json = <<<JSON
{
    "status": "failed",
    "is_ok": false
}
JSON;

		Assert::contains($json, $display);
		Assert::contains($message, $display);
	}

	private function executeCommand(HealthCheckerInterface $healthChecker, ?array $servicesOnly, ?ExportMode $exportMode): CommandTester
	{
		$application = new Application();
		$application->add(new HealthCheckCommand($healthChecker));

		$command = $application->find('health-check');
		$tester = new CommandTester($command);
		$input = [];

		if (NULL !== $servicesOnly) {
			$input['services'] = $servicesOnly;
		}

		if (NULL !== $exportMode) {
			$input['--export-mode'] = $exportMode->value;
		}

		$tester->execute($input);

		return $tester;
	}
}

(new HealthCheckCommandTest())->run();
