FROM php:7.3-alpine

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

RUN pecl install mongodb-1.9.0 \
    && docker-php-ext-enable mongodb

RUN pecl install xdebug-2.9.7 \
    && docker-php-ext-enable xdebug

COPY config/php.ini /usr/local/etc/php/conf.d/

#COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN adduser -u 1000 -G wheel -D user-dev -s /bin/zsh
USER user-dev

#Zsh minimal installation
RUN git clone --depth=1 git://github.com/robbyrussell/oh-my-zsh.git  ~/.oh-my-zsh
ADD config/.zshrc /root/
ADD config/.zshrc /home/user-dev/

WORKDIR /home/user-dev/project

ENV TERM xterm-256color
ENV PHP_IDE_CONFIG="serverName=user-dev"
ENV XDEBUG_CONFIG="remote_host=172.18.0.1 remote_port=9000"
