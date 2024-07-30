FROM php:7.3-fpm-alpine

RUN apk update \
  && apk add autoconf g++ make libzip-dev jpeg-dev libpng-dev zip imap-dev krb5-dev openssl-dev \
  && docker-php-ext-configure imap --with-imap-ssl \
  && docker-php-ext-install imap \
  && docker-php-ext-install bcmath \
  && docker-php-ext-enable bcmath \
  && docker-php-ext-enable imap \
  && docker-php-ext-configure gd --with-jpeg-dir=/usr/lib \
  && docker-php-ext-install gd \
  && docker-php-ext-enable gd \
  && docker-php-ext-install zip \
  && docker-php-ext-install mysqli \
  && docker-php-ext-install pdo_mysql \
  && docker-php-ext-enable mysqli \
  && apk del autoconf g++ make \
  && rm -rf /tmp/pear

RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

COPY ./common/php/php.ini $PHP_INI_DIR/php.ini
#COPY ./common/php/conf.d /usr/local/etc/php/conf.d

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./php/docker-php-entrypoint.sh /usr/local/bin/docker-php-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-php-entrypoint.sh

WORKDIR /app