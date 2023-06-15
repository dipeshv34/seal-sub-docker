FROM php:8.1-fpm-alpine

RUN apk add --no-cache nginx wget

RUN apk update && apk add --no-cache supervisor

RUN docker-php-ext-install pcntl
RUN docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql

RUN mkdir -p /run/nginx

COPY docker/nginx.conf /etc/nginx/nginx.conf

RUN mkdir -p /app
COPY . /app
COPY ./src /app

RUN sh -c "wget http://getcomposer.org/composer.phar && chmod a+x composer.phar && mv composer.phar /usr/local/bin/composer"
RUN cd /app && \
    /usr/local/bin/composer install --no-dev

RUN chown -R www-data: /app

# copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80

# run supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
CMD sh /app/docker/startup.sh