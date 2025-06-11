<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Omni\Application\Apitte\Controller;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\UI\Controller\IController;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;

#[Path('/api/health-check')]
final class HealthCheckController implements IController
{
	public function __construct(
		private readonly HealthCheckerInterface $healthChecker,
	) {
	}

	#[Path('/')]
	#[Method('GET')]
	public function index(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$result = $this->healthChecker->check();

		return $response
			->withStatus($result->isOk() ? ApiResponse::S200_OK : ApiResponse::S503_SERVICE_UNAVAILABLE)
			->writeJsonObject($result);
	}
}
