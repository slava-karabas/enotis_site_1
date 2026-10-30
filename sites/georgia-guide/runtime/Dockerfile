FROM php:8.3-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    libzip-dev libonig-dev libcurl4-openssl-dev libsqlite3-dev \
    curl unzip sqlite3 python3 \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install -j2 pdo_sqlite gd zip mbstring curl \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY enotis-feed/ ./
COPY entrypoint.sh /usr/local/bin/enotis-preview
RUN chmod +x /usr/local/bin/enotis-preview \
    && find tests -name '*.sh' -exec chmod +x {} \;
ENV ENOTIS_DATA=/data
ENTRYPOINT ["/usr/local/bin/enotis-preview"]
