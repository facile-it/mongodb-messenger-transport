ARG PHP_VERSION=7.3
FROM php:${PHP_VERSION}-alpine

RUN apk --no-cache add \
        $PHPIZE_DEPS \
        curl \
        git \
        less \
        libzip-dev \
        nano \
        openssl-dev \
        supervisor \
        zip \
        zsh

ARG MONGO_VERSION=1.9.2
RUN pecl install mongodb-${MONGO_VERSION} \
    && docker-php-ext-enable mongodb

ARG XDEBUG_VERSION=2.9.7
RUN pecl install xdebug-${XDEBUG_VERSION} \
    && docker-php-ext-enable xdebug

COPY config/php.ini /usr/local/etc/php/conf.d/

#COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN adduser -u 1000 -G wheel -D user-dev -s /bin/zsh
USER user-dev

#Zsh minimal installation
RUN sh -c "$(curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh)"
ADD config/.zshrc /root/
ADD config/.zshrc /home/user-dev/

WORKDIR /home/user-dev/project

ENV TERM xterm-256color
ENV PHP_IDE_CONFIG="serverName=user-dev"
ENV XDEBUG_CONFIG="remote_host=172.18.0.1 remote_port=9000"
