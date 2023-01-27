# docker build -t unitely/osticket:latest .
# docker run -d -p 80:80 --name unitely_osticket unitely/osticket

FROM php:8.1-apache

ENV ADMIN_EMAIL='support@email.com'
ENV DBHOST='database'
ENV DBNAME='dbname'
ENV DBPASS='somepassword'
ENV DBTYPE='mysql'
ENV DBUSER='dbuser'
ENV SALT='1234567890'

# OS Packages
RUN apt-get update -y && apt-get install -yf cron libpng-dev libc-client-dev libkrb5-dev libicu-dev && \
    rm -r /var/lib/apt/lists/*

# PHP modules
RUN pecl channel-update pecl.php.net && \
    pecl install apcu && \
    docker-php-ext-install mysqli && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && docker-php-ext-install imap && \
    docker-php-ext-install gd && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl && \
    docker-php-ext-enable apcu && \
    docker-php-ext-install opcache

# Apache module needed for OAuth API endpoints
RUN a2enmod rewrite

ARG OST_ROOT=/var/www/html

COPY ./php.ini-development $PHP_INI_DIR/php.ini

RUN rm -rf $OST_ROOT/*

COPY . $OST_ROOT

RUN rm -rf $OST_ROOT/setup
RUN rm -f $OST_ROOT/php.ini-development

RUN chmod 644 $OST_ROOT/include/ost-config.php

ARG CRON_TASK="*/5 * * * * /usr/local/bin/php -c $PHP_INI_DIR/php.ini $OST_ROOT/api/cron.php"
ARG TEMP_CRON_FILE="${OST_ROOT}/crontab"
RUN echo "${CRON_TASK}\n" >> $TEMP_CRON_FILE
RUN crontab -u www-data ${TEMP_CRON_FILE}
RUN rm ${TEMP_CRON_FILE}

# Run cron service as part of container entrypoint
RUN sed -i "s/^exec /printenv | grep -E 'ADMIN_EMAIL|DBHOST|DBNAME|DBPASS|DBTYPE|DBUSER|SALT' > \/etc\/environment\nservice cron start\n\nexec /" /usr/local/bin/apache2-foreground
