COMPOSER_RUN=docker run --rm --tty --volume "${PWD}":/app composer
PHP_RUN=docker run --rm --workdir /app --volume "${PWD}":/app php:7.1-cli-alpine

composer-install:
	$(COMPOSER_RUN) install --prefer-dist

composer-update:
	$(COMPOSER_RUN) update --prefer-dist

test:
	$(PHP_RUN) php vendor/bin/phpunit --stop-on-failure