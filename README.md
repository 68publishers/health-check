<h1 align="center">Health check</h1>

<p align="center">:heartpulse: Health check for external services that are important for your application.</p>

<p align="center">
<a href="https://github.com/68publishers/health-check/actions"><img alt="Checks" src="https://badgen.net/github/checks/68publishers/health-check/master"></a>
<a href="https://coveralls.io/github/68publishers/health-check?branch=master"><img alt="Coverage Status" src="https://coveralls.io/repos/github/68publishers/health-check/badge.svg?branch=master"></a>
<a href="https://packagist.org/packages/68publishers/health-check"><img alt="Total Downloads" src="https://badgen.net/packagist/dt/68publishers/health-check"></a>
<a href="https://packagist.org/packages/68publishers/health-check"><img alt="Latest Version" src="https://badgen.net/packagist/v/68publishers/health-check"></a>
<a href="https://packagist.org/packages/68publishers/health-check"><img alt="PHP Version" src="https://badgen.net/packagist/php/68publishers/health-check"></a>
</p>

## Installation

The best way to install `68publishers/health-check` is using Composer:

```bash
$ composer require 68publishers/health-check
```

## Standalone usage

```php
use SixtyEightPublishers\HealthCheck\ExportMode;
use SixtyEightPublishers\HealthCheck\HealthChecker;
use SixtyEightPublishers\HealthCheck\ServiceChecker\PDOServiceChecker;
use SixtyEightPublishers\HealthCheck\ServiceChecker\RedisServiceChecker;

$checker = new HealthChecker();
$checker->addServiceChecker(new PDOServiceChecker('pgsql:host=127.0.0.1;port=5432;dbname=example', 'user', 'password'));
$checker->addServiceChecker(new RedisServiceChecker())

# check all services
$result = $checker->check();

# you can throw an exception
if (!$result->isOk()) {
    throw $result->getError();
}

# or covert the result into a JSON
echo json_encode($result);

# check Redis only
$result = $checker->check(['redis']);

# check in the "Full" mode. The default mode is "Simple".
$result = $checker->check(NULL, ExportMode::Full);

# the result now contains detailed information about each service
echo json_encode($result);
```

## Available service checkers

- PDO - `SixtyEightPublishers\HealthCheck\ServiceChecker\PDOServiceChecker`
- Redis - `SixtyEightPublishers\HealthCheck\ServiceChecker\RedisServiceChecker`
- Http - `SixtyEightPublishers\HealthCheck\ServiceChecker\HttpServiceChecker`

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
    export_mode: full_if_debug # This is the default value. Supported values are "full_if_debug", "full", "simple" or custom service that implements an interface "ExportModeResolverInterface".
```

Now the service of type `SixtyEightPublishers\HealthCheck\HealthCheckerInterface` is accessible in DIC.

### Health check using Symfony Console

```neon
extensions:
    68publishers.health_check: SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\HealthCheckExtension
    68publishers.health_check.console: SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\HealthCheckConsoleExtension
```

Now you can run this command:

```bash
$ bin/console health-check [<services>] [--export-mode <mode>]
```

### Health check using Nette Application

```neon
extensions:
    68publishers.health_check: SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\HealthCheckExtension
    68publishers.health_check.application: SixtyEightPublishers\HealthCheck\Bridge\Nette\DI\HealthCheckApplicationExtension

68publishers.health_check.application:
    route: '/health-check' # The default value. You can change it or set it as "false".
```

The extension automatically appends the health check route into your RouteList. If you want to disable this behaviour, please set the option `route` to `false` and add the route to your route factory manually e.g.:

```php
<?php

namespace App;

use Nette\Application\Routers\RouteList;
use SixtyEightPublishers\HealthCheck\Bridge\Nette\Application\HealthCheckRoute;

class RouteFactory {
    public static function create(): RouteList {
        $router = new RouteList();
        $router->add(new HealthCheckRoute('/health-check'));
        
        # ... other routes ...
        
        return $router;
    }
}
```

Now you can check your services through an endpoint `your-domain.com/health-check`.
The endpoint returns the status code `200` if everything is ok and `503` if some service check failed.

## Contributing

Before opening a pull request, please check your changes using the following commands

```bash
$ make init # to pull and start all docker images

$ make cs.check
$ make stan
$ make tests.all
```
