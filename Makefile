.PHONY: up setup start test

docker-compose.override.yml:
	cp docker-compose.override.yml.dist docker-compose.override.yml

docker-compose.yml: docker-compose.override.yml

up: docker-compose.yml
	docker-compose up -d

setup: docker-compose.yml composer.json
	docker-compose run --no-deps --rm php composer install

start: up
	docker-compose exec php zsh

stop: docker-compose.yml
	docker-compose stop

infection:
	docker-compose run --rm php zsh -c "vendor/bin/infection --threads=8 --show-mutations --min-msi=84"

test: docker-compose.yml phpunit.xml.dist
	docker-compose run --rm php zsh -c "vendor/bin/phpunit -c phpunit.xml.dist"

phpstan: docker-compose.yml
	docker-compose run --no-deps --rm php zsh -c "vendor/bin/phpstan analyze --memory-limit=-1"

cs-fix: docker-compose.yml
	docker-compose run --no-deps --rm php zsh -c "composer cs-fix"

lock-symfony-%: SYMFONY_VERSION = $*
lock-symfony-%:
	rm composer.lock || true
	docker-compose run --no-deps --rm php composer config extra.symfony.require "${SYMFONY_VERSION}.*"
	docker-compose run --no-deps --rm php composer install --prefer-dist --no-interaction ${COMPOSER_FLAGS}

test-composer-install: lock-symfony-3.4 lock-symfony-4.4 lock-symfony-5.0 lock-symfony-6.0

pre-commit-checks: cs-fix phpstan test infection
