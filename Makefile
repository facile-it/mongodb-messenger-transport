.PHONY: up setup start test

docker-compose.override.yml:
	cp docker-compose.override.yml.dist docker-compose.override.yml

docker-compose.yml: docker-compose.override.yml

up: docker-compose.yml
	docker-compose up -d --force-recreate

setup: docker-compose.yml composer.json
	docker-compose run --rm php composer install

start: up
	docker-compose exec php bash

stop: docker-compose.yml
	docker-compose stop

test: docker-compose.yml phpunit.xml.dist
	docker-compose run --rm php bash -c "bin/phpunit -c phpunit.xml.dist"

phpstan: docker-compose.yml
	docker-compose run --rm php bash -c "bin/phpstan analyze -l7 src/ tests/"

lock-symfony-%: SYMFONY_VERSION = $*
lock-symfony-%:
	rm composer.lock || true
	docker-compose run --no-deps --rm php composer config extra.symfony.require "${SYMFONY_VERSION}.*"
	docker-compose run --no-deps --rm php composer install --prefer-dist --no-interaction ${COMPOSER_FLAGS}

test-composer-install: lock-symfony-3.4 lock-symfony-4.4 lock-symfony-5.0
