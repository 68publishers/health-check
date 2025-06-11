init:
	make stop
	make start

stop:
	docker compose --env-file .env --profile default stop

start:
	docker compose --env-file .env --profile default up -d

start-services:
	docker compose --env-file .env --profile services up -d

down:
	docker compose --env-file .env --profile default down

restart:
	make stop
	make start

tests.all:
	PHP=81 make tests.run
	PHP=82 make tests.run
	PHP=83 make tests.run
	PHP=84 make tests.run

cs.fix:
	PHP=81 make composer.update
	docker exec 68publishers.health-check.81 vendor/bin/php-cs-fixer fix -v

cs.check:
	PHP=81 make composer.update
	docker exec 68publishers.health-check.81 vendor/bin/php-cs-fixer fix -v --dry-run

stan:
	PHP=81 make composer.update
	docker exec 68publishers.health-check.81 vendor/bin/phpstan analyse --memory-limit=-1

coverage:
	PHP=81 make composer.update
	docker exec 68publishers.health-check.81 vendor/bin/tester -C -s --coverage ./coverage.xml $(shell find ./src -type f -name '*.php' ! -path './src/Bridge/Omni/*' -exec echo --coverage-src {} \;) ./tests

composer.update:
ifndef PHP
	$(error "PHP argument not set.")
endif
	@echo "========== Installing dependencies with PHP $(PHP) ==========" >&2
	docker exec 68publishers.health-check.$(PHP) composer update --no-progress --prefer-dist --prefer-stable --optimize-autoloader --quiet

composer.update-lowest:
ifndef PHP
	$(error "PHP argument not set.")
endif
	@echo "========== Installing dependencies with PHP $(PHP) (prefer lowest dependencies) ==========" >&2
	docker exec 68publishers.health-check.$(PHP) composer update --no-progress --prefer-dist --prefer-lowest --prefer-stable --optimize-autoloader --quiet

tests.run:
ifndef PHP
	$(error "PHP argument not set.")
endif
	PHP=$(PHP) make composer.update
	@echo "========== Running tests with PHP $(PHP) ==========" >&2
	docker exec 68publishers.health-check.$(PHP) vendor/bin/tester -C -s ./tests
	PHP=$(PHP) make composer.update-lowest
	@echo "========== Running tests with PHP $(PHP) (prefer lowest dependencies) ==========" >&2
	docker exec 68publishers.health-check.$(PHP) vendor/bin/tester -C -s ./tests
