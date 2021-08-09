# Health check

[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![Latest Version on Packagist][ico-version]][link-packagist]

:heartpulse: Health check for external services that are important for your application.

## Installation

The best way to install `68publishers/health-check` is using Composer:

```bash
$ composer require 68publishers/health-check
```

## Standalone usage

```php
<?php

use SixtyEightPublishers\HealthCheck\HealthChecker;
use SixtyEightPublishers\HealthCheck\ServiceChecker\PDOServiceChecker;
use SixtyEightPublishers\HealthCheck\ServiceChecker\RedisServiceChecker;

$checker = new HealthChecker();
$checker->addServiceChecker(new PDOServiceChecker('pgsql:host=127.0.0.1;port=5432;dbname=example', 'user', 'password'));
$checker->addServiceChecker(new RedisServiceChecker())

# check all services
$result = $checker->check();

if ($result->isOk()) {
    echo 'OK';
} else {
    throw $result->getError();
}

# check Redis only
$result = $checker->check(['redis']);

#...
```

## Available service checkers

- Database using PDO (`SixtyEightPublishers\HealthCheck\ServiceChecker\PDOServiceChecker`)
- Redis (`SixtyEightPublishers\HealthCheck\ServiceChecker\RedisServiceChecker`)
- Http (`SixtyEightPublishers\HealthCheck\ServiceChecker\HttpServiceChecker`)

You can create your own service checker. Just create a class that implements the interface `ServiceCheckerInterface`.

## Integration into Nette Framework

The package provides compiler extensions for easy integration with Nette Framework.

### Configuration example

```neon
extensions:
	68publishers.health_check: SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\HealthCheckExtension

68publishers.health_check:
	service_checkers:
		- SixtyEightPublishers\HealthCheck\ServiceChecker\RedisServiceChecker()
		- SixtyEightPublishers\HealthCheck\ServiceChecker\PDOServiceChecker::fromParams([
			driver: pgsql
			host: '127.0.0.1'
			port: 5432
			dbname: example
			user: user
			password: password
		])
		- MyCustomServiceChecker('foo')
```

Now the service of type `SixtyEightPublishers\HealthCheck\HealthCheckerInterface` is accessible in DIC.

### Health check using Symfony Console

```neon
extensions:
	68publishers.health_check.console: SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\HealthCheckConsoleExtension
```

Now you can run this command:

```bash
$ bin/console health-check [<services>] [--full]
```

An array argument `services` represents the names of services for a check. The argument is optional so all services are checked when the argument is omitted.

An output is simplified by default:

```bash
$ bin/console health-check
{
    "status": "ok",
    "is_ok": true
}
```

```bash
$ bin/console health-check
{
    "status": "failed",
    "is_ok": false
}
```

But when an option `--full` is present the output contains information about all services:

```bash
$ bin/console health-check
{
    "status": "failed",
    "is_ok": false,
    "services": [
        {
            "name": "redis",
            "is_ok": true,
            "status": "running",
            "error": null
        },
        {
            "name": "database",
            "is_ok": true,
            "status": "running",
            "error": null
        },
        {
            "name": "foo",
            "is_ok": false,
            "status": "down",
            "error": "The service responds with a status code 500."
        }
    ]
}
```

### Health check using an endpoint

Create a Presenter like this:

```php
<?php

use SixtyEightPublishers\HealthCheck\HealthCheckerInterface;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\UI\AbstractHealthCheckPresenter;

final class HealthCheckPresenter extends AbstractHealthCheckPresenter
{
    protected function getArrayExportMode() : string
    {
        # you can resolve an export mode in this method, use following constants:

        # 1) HealthCheckerInterface::ARRAY_EXPORT_MODEL_SIMPLE
        # 2) HealthCheckerInterface::ARRAY_EXPORT_MODE_FULL

        return HealthCheckerInterface::ARRAY_EXPORT_MODE_FULL;
    }
}
```

Then add a route in your Router Factory:

```php
<?php

/** @var \Nette\Routing\Router $router */

$router->addRoute('health-check', 'HealthCheck:default');
```

Now you can check your services through an endpoint `your-domain.com/health-check`.
The endpoint returns a status code `200` if everything is ok and `503` if some service check failed.

## Contributing

Before committing any changes, don't forget to run

```bash
$ composer run php-cs-fixer
```

and

```bash
$ composer run tests
```

[ico-version]: https://img.shields.io/packagist/v/68publishers/health-check.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/68publishers/health-check/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/68publishers/health-check.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/68publishers/health-check.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/68publishers/health-check.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/68publishers/health-check
[link-travis]: https://travis-ci.org/68publishers/health-check
[link-scrutinizer]: https://scrutinizer-ci.com/g/68publishers/health-check/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/68publishers/health-check
[link-downloads]: https://packagist.org/packages/68publishers/health-check
