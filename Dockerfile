
FROM php:8.2-apache

# Install SQLite extensions required by CampusPro
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite sqlite3 \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html/

# Persistent storage for database and uploads
ENV CAMPUSPRO_DATA_DIR=/var/data

RUN mkdir -p /var/data/uploads/content /var/data/uploads/fees /var/data/campuspro_sessions \
    && chown -R www-data:www-data /var/data \
    && chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["sh", "-c", "if [ ! -f /var/data/campus.db ] && [ -f /var/www/html/campus.db ]; then cp /var/www/html/campus.db /var/data/campus.db; fi; chown -R www-data:www-data /var/data; exec apache2-foreground"]
