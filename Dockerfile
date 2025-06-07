FROM php:8.1.12-cli-alpine3.16 AS php81

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apk add --no-cache ${PHPIZE_DEPS} postgresql-libs postgresql-dev pcre-dev \
	&& apk --no-cache add postgresql-libs postgresql-dev \
    && pecl install pcov \
    && pecl install uopz-7.1.1 \
    && pecl install redis \
    && docker-php-ext-install pgsql pdo_pgsql \
    && docker-php-ext-enable pcov uopz redis.so

CMD tail -f /dev/null

FROM php:8.2.28-cli-alpine3.21 AS php82

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apk add --no-cache ${PHPIZE_DEPS} postgresql-libs postgresql-dev pcre-dev \
    && pecl install pcov \
    && pecl install uopz-7.1.1 \
    && pecl install redis \
    && docker-php-ext-install pgsql pdo_pgsql \
    && docker-php-ext-enable pcov uopz redis.so

CMD tail -f /dev/null

FROM php:8.3.12-cli-alpine3.20 AS php83

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apk add --no-cache ${PHPIZE_DEPS} postgresql-libs postgresql-dev pcre-dev \
    && pecl install pcov \
    && pecl install uopz-7.1.1 \
    && pecl install redis \
    && docker-php-ext-install pgsql pdo_pgsql \
    && docker-php-ext-enable pcov uopz redis.so

CMD tail -f /dev/null

FROM php:8.4.4-cli-alpine3.21 AS php84

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apk add --no-cache ${PHPIZE_DEPS} postgresql-libs postgresql-dev pcre-dev \
    && mkdir -p /usr/src/php/ext/uopz \
    && curl -fsSL https://github.com/zonuexe/uopz/archive/refs/heads/support/php84-exit.tar.gz | tar xvz -C /usr/src/php/ext/uopz --strip 1 \
    && docker-php-ext-install uopz \
    && pecl install pcov \
    && pecl install redis \
    && docker-php-ext-install pgsql pdo_pgsql \
    && docker-php-ext-enable pcov uopz redis.so

CMD tail -f /dev/null
