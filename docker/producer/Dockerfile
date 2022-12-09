FROM php:8.1.13-cli-alpine3.17

RUN apk add --update \
    bash \
    && docker-php-ext-install sockets

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker-entrypoint.sh /usr/local/bin/
RUN ["chmod", "+x", "/usr/local/bin/docker-entrypoint.sh"]
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
