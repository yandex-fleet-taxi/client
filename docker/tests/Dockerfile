FROM php:7-alpine

RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS && pecl install xdebug-2.7.0 && docker-php-ext-enable xdebug
#RUN export PHP_IDE_CONFIG="serverName=Docker"

COPY docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
