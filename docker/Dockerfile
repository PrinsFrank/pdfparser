FROM php:8.5-cli-alpine

COPY --from=ghcr.io/php/pie:bin /pie /usr/bin/pie

RUN apk add --no-cache --virtual .build-deps \
    autoconf \
    g++ \
    make \
    zlib-dev \
    && apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && pie install noisebynorthwest/php-spx \
    && mkdir -p /opt/spx && echo '<?php declare(strict_types=1);' > /opt/spx/spx.php \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/tmp/* /var/cache/apk/* /usr/bin/pie

COPY composer-install.sh .
RUN chmod +x ./composer-install.sh \
    && ./composer-install.sh \
    && rm ./composer-install.sh
