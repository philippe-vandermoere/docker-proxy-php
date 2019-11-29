# Docker-proxy PHP

![CircleCI](https://img.shields.io/circleci/build/github/philippe-vandermoere/docker-proxy-php)
[![codecov](https://codecov.io/gh/philippe-vandermoere/docker-proxy-php/branch/master/graph/badge.svg)](https://codecov.io/gh/philippe-vandermoere/docker-proxy-php)

PHP implementation of [docker-proxy](https://github.com/philippe-vandermoere/docker-proxy).

## Development

### Installation

```bash
docker-compose run php composer install
```

### Start

Run project

```bash
docker-compose run php bin/console proxy:run
```

### Test

#### Code Sniffer

```bash
docker-compose run php make phpcs
```

#### Stan

```bash
docker-compose run php make phpstan
```

#### Unit

```bash
docker-compose run php make phpunit
```
