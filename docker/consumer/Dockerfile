FROM php:8.1.13-cli-alpine3.17 AS consumer

RUN apk add --update \
    bash \
    supervisor \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install sockets

COPY supervisord.conf /etc/
ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
