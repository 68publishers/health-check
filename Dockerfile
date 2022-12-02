FROM php:8.1.12-cli-alpine3.16 AS php81

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN set -ex \
	&& apk --no-cache add postgresql-libs postgresql-dev \
	&& docker-php-ext-install pgsql pdo_pgsql \
	&& apk del postgresql-dev

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
	&& pecl install redis \
	&& docker-php-ext-enable redis.so

CMD tail -f /dev/null

FROM php:8.2.0RC6-cli-alpine3.16 AS php82

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN set -ex \
	&& apk --no-cache add postgresql-libs postgresql-dev \
	&& docker-php-ext-install pgsql pdo_pgsql \
	&& apk del postgresql-dev

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
	&& pecl install redis \
	&& docker-php-ext-enable redis.so

CMD tail -f /dev/null
