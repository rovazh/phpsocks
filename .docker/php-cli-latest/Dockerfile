FROM php:8.3-alpine3.22 AS builder

WORKDIR /app

RUN set -eux; \
    apk add --no-cache git unzip curl bash linux-headers; \
    docker-php-ext-install sockets; \
    \
    curl -sS https://getcomposer.org/installer -o composer-setup.php; \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer; \
    rm composer-setup.php

COPY composer.json ./

RUN composer install --prefer-dist --no-progress --no-interaction

FROM php:8.3-alpine3.22

RUN set -eux; \
    apk add --no-cache \
        bash \
        git \
        zip \
        unzip \
        libzip-dev \
        autoconf \
        g++ \
        make \
        linux-headers; \
    \
    docker-php-ext-install sockets; \
    \
    pecl install xdebug-3.4.4; \
    docker-php-ext-enable xdebug; \
    \
    { \
        echo "zend_extension=xdebug.so"; \
        echo "xdebug.mode=coverage"; \
        echo "xdebug.start_with_request=no"; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    \
    apk del autoconf g++ make linux-headers

WORKDIR /app

COPY --from=builder /app/vendor /app/vendor
COPY . .

CMD ["php", "-v"]
