<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Bridge\Nette\UI;

use Nette\Application\Request;
use Nette\Application\IPresenter;
use Nette\Http\IResponse as HttpResponse;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\IResponse as ApplicationResponse;
use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;

abstract class AbstractHealthCheckPresenter implements IPresenter
{
	private HealthCheckerInterface $healthChecker;

	protected HttpResponse $response;

	/**
	 * @param \SixtyEightPublishers\HealthCheck\HealthCheckerInterface $healthChecker
	 * @param \Nette\Http\IResponse                                    $response
	 */
	public function __construct(HealthCheckerInterface $healthChecker, HttpResponse $response)
	{
		$this->healthChecker = $healthChecker;
		$this->response = $response;
	}

	/**
	 * {@inheritDoc}
	 */
	public function run(Request $request): ApplicationResponse
	{
		$result = $this->healthChecker->check([], $this->getArrayExportMode());

		$this->response->setCode($result->isOk() ? HttpResponse::S200_OK : HttpResponse::S503_SERVICE_UNAVAILABLE);

		return new JsonResponse($result);
	}

	/**
	 * @return string
	 */
	protected function getArrayExportMode(): string
	{
		return HealthCheckerInterface::ARRAY_EXPORT_MODEL_SIMPLE;
	}
}
