FROM wordpress:latest

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/error_reporting.ini

RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=whateveryourportis" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=whateveryourkeyis" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini