<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\Application;

use Nette\Application\Request;
use Nette\Application\IPresenter;
use Nette\Http\IResponse as HttpResponse;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Response as ApplicationResponse;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;

class HealthCheckPresenter implements IPresenter
{
	public function __construct(
		private readonly HealthCheckerInterface $healthChecker,
		private readonly HttpResponse $response,
	) {
	}

	public function run(Request $request): ApplicationResponse
	{
		$result = $this->healthChecker->check();

		$this->response->setCode($result->isOk() ? HttpResponse::S200_OK : HttpResponse::S503_ServiceUnavailable);

		return new JsonResponse($result);
	}
}
