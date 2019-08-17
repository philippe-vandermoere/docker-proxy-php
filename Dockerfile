FROM php:7.3.8-cli-alpine

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /app

RUN set -xe; \
    composer global require hirak/prestissimo; \
    composer install --no-dev --classmap-authoritative --no-progress --no-interaction;

ENTRYPOINT ["/usr/bin/env"]

CMD ["bin/console", "proxy:start"]
