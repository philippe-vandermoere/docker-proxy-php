# Docker-proxy PHP

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
docker-compose run php bin/phpcs
```

#### Stan

```bash
docker-compose run php bin/phpstan
```

#### Unit

```bash
docker-compose run php bin/phpunit
```
