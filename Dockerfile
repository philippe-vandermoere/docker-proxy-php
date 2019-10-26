FROM php:7.3.11-cli-alpine

WORKDIR /app

COPY . /app

RUN set -xe; \
    curl -sl https://getcomposer.org/composer.phar -o /usr/local/bin/composer; \
    chmod +x /usr/local/bin/composer; \
    composer global require hirak/prestissimo; \
    composer install --no-dev --classmap-authoritative --no-progress --no-interaction;

CMD ["bin/console", "proxy:start"]
