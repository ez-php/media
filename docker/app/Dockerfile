FROM au9500/php:8.5

# gd/imagick are not part of the base image's extension set — install them here
# rather than relying on the base image, since this module is the only one
# that needs image codec support.
COPY --from=ghcr.io/php/pie:1.5.5-bin /pie /usr/bin/pie

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libmagickwand-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install gd \
    && pie install imagick/imagick \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

CMD ["/usr/local/bin/container-start.sh"]
