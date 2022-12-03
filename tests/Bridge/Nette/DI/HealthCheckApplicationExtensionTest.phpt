<?php

declare(strict_types=1);

namespace SixtyEightPublishers\HealthCheck\Tests\Bridge\Nette\DI;

use Tester\Assert;
use Tester\TestCase;
use RuntimeException;
use Nette\Http\Request;
use Nette\Utils\Helpers;
use Nette\Http\UrlScript;
use Nette\Application\Application;
use Tester\CodeCoverage\Collector;
use Nette\Http\IResponse as HttpResponse;
use function assert;

require __DIR__ . '/../../../bootstrap.php';

final class HealthCheckApplicationExtensionTest extends TestCase
{
	public function testExceptionShouldBeThrownIfHealthCheckExtensionNotRegistered(): void
	{
		Assert::exception(
			static function () {
				ContainerFactory::create(__DIR__ . '/config.application.error.missingHealthCheckExtension.neon', true);
			},
			RuntimeException::class,
			"Please register the compiler extension of type SixtyEightPublishers\\HealthCheck\\Bridge\\Nette\\DI\\HealthCheckExtension."
		);
	}

	public function testApplicationShouldReturn404IfRouteNotRegistered(): void
	{
		$this->assertResponse(
			__DIR__ . '/config.application.error.routeNotRegistered.neon',
			'http://localhost.dev/health-check',
			HttpResponse::S404_NotFound,
			''
		);
	}

	public function testApplicationWithMinimalConfiguration(): void
	{
		$this->assertResponse(
			__DIR__ . '/config.application.minimal.neon',
			'http://localhost.dev/health-check',
			HttpResponse::S200_OK,
			'{"status":"ok","is_ok":true,"services":[{"name":"first","is_ok":true,"status":"healthy","error":null},{"name":"second","is_ok":true,"status":"healthy","error":null}]}'
		);
	}

	public function testApplicationWithManuallyRegisteredRoute(): void
	{
		$this->assertResponse(
			__DIR__ . '/config.application.withManuallyRegisteredRoute.neon',
			'http://localhost.dev/health-check',
			HttpResponse::S200_OK,
			'{"status":"ok","is_ok":true,"services":[{"name":"first","is_ok":true,"status":"healthy","error":null},{"name":"second","is_ok":true,"status":"healthy","error":null}]}'
		);
	}

	public function testApplicationWithRouteOption(): void
	{
		$this->assertResponse(
			__DIR__ . '/config.application.withRouteOption.neon',
			'http://localhost.dev/api/health-check',
			HttpResponse::S503_ServiceUnavailable,
			'{"status":"failed","is_ok":false,"services":[{"name":"first","is_ok":true,"status":"healthy","error":null},{"name":"second","is_ok":false,"status":"unhealthy","error":"Service is unhealthy."}]}'
		);
	}

	private function assertResponse(string $config, string $url, int $expectedStatusCode, string $expectedResponse): void
	{
		$container = ContainerFactory::create($config, true);

		$container->addService('http.request', new Request(new UrlScript($url)));

		$httpResponse = $container->getByType(HttpResponse::class);
		$application = $container->getByType(Application::class);
		assert($httpResponse instanceof HttpResponse && $application instanceof Application);

		$output = Helpers::capture(static fn () => $application->run());

		Assert::same($expectedStatusCode, $httpResponse->getCode());
		Assert::same($expectedResponse, $output);
	}

	protected function tearDown(): void
	{
		# save manually partial code coverage to free memory
		if (Collector::isStarted()) {
			Collector::save();
		}
	}
}

(new HealthCheckApplicationExtensionTest())->run();
