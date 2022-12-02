<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Bridge\Nette\Application;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use Nette\Http\IResponse as HttpResponse;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Request as ApplicationRequest;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;
use SixtyEightPublishers\HealthCheck\Result\HealthCheckResult;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\Application\HealthCheckPresenter;
use function assert;

require __DIR__ . '/../../../bootstrap.php';

final class HealthCheckPresenterTest extends TestCase
{
	public function testHealthyResponse(): void
	{
		$this->assertResponse(HttpResponse::S200_OK, TRUE);
	}

	public function testUnhealthyResponse(): void
	{
		$this->assertResponse(HttpResponse::S503_ServiceUnavailable, FALSE);
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}

	private function assertResponse(int $statusCode, bool $healthy): void
	{
		$checker = Mockery::mock(HealthCheckerInterface::class);
		$result = Mockery::mock(HealthCheckResult::class);
		$httpResponse = Mockery::mock(HttpResponse::class);
		$request = Mockery::mock(ApplicationRequest::class);

		$checker->shouldReceive('check')
			->once()
			->withNoArgs()
			->andReturn($result);

		$result->shouldReceive('isOk')
			->once()
			->andReturn($healthy);

		$httpResponse->shouldReceive('setCode')
			->once()
			->with($statusCode)
			->andReturnSelf();

		$presenter = new HealthCheckPresenter($checker, $httpResponse);
		$response = $presenter->run($request);

		Assert::type(JsonResponse::class, $response);
		assert($response instanceof JsonResponse);
		Assert::same($result, $response->getPayload());
	}
}

(new HealthCheckPresenterTest())->run();
