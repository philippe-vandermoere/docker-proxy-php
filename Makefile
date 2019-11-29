default:

test: phpcs phpstan phpunit

phpcs:
	vendor/bin/phpcs

phpstan:
	vendor/bin/phpstan analyse

phpunit:
	phpdbg -qrr vendor/bin/phpunit

docker_build:
	docker build . \
		--build-arg VCS_REF=$(git rev-parse --short HEAD) \
		--build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
