FROM php:7.4.0-cli-alpine

ARG BUILD_DATE
ARG VCS_REF

LABEL maintainer="Philippe VANDERMOERE <philippe@wizacha.com" \
    org.label-schema.build-date=${BUILD_DATE} \
    org.label-schema.name="docker-proxy-php" \
    org.label-schema.vcs-ref=${VCS_REF} \
    org.label-schema.vcs-url="https://github.com/philippe-vandermoere/docker-proxy-php" \
    org.label-schema.schema-version="1.0.0"

WORKDIR /app

COPY . /app

RUN set -xe; \
    curl -sl https://getcomposer.org/composer.phar -o /usr/local/bin/composer; \
    chmod +x /usr/local/bin/composer; \
    composer global require hirak/prestissimo; \
    composer install --no-dev --classmap-authoritative --no-progress --no-interaction;

CMD ["bin/console", "proxy:start"]
