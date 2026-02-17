FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
  git unzip curl \
  libzip-dev \
  libicu-dev \
  libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
  && docker-php-ext-install pdo_mysql zip pcntl bcmath \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd \
  && pecl install redis && docker-php-ext-enable redis \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Entrypoint script to ensure dependencies are installed
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

