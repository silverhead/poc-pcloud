FROM node:lts-bullseye-slim AS node
FROM php:8.1-apache

ARG APP_DIR
ARG DOCKER_USER
ARG DOCKER_USER_ID
ARG GIT_USER_NAME
ARG GIT_USER_EMAIL

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf;

RUN a2enmod rewrite

RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y locales apt-utils git libicu-dev g++ libpng-dev \
    libxml2-dev libzip-dev libonig-dev libxslt-dev gconf-service libasound2 \
    libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 \
    libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 \
    libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 \
    libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 \
    libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libnss3 lsb-release \
    xdg-utils wget libgbm-dev libxshmfence-dev zip \ 
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

RUN     echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
        echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
        locale-gen

RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo pdo_mysql gd opcache intl zip calendar dom mbstring gd xsl pcntl sockets xml
RUN pecl install apcu && docker-php-ext-enable apcu

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
    mv composer.phar /usr/local/bin/composer

RUN git config --global user.name ${GIT_USER_NAME}; \
    git config --global user.email ${GIT_USER_EMAIL}

WORKDIR /var/www/${APP_DIR}

RUN groupadd --g ${DOCKER_USER_ID} ${DOCKER_USER}; \
	useradd -u ${DOCKER_USER_ID} -g ${DOCKER_USER} -g www-data -m ${DOCKER_USER};
RUN	chown ${DOCKER_USER}:${DOCKER_USER} /var/www/${APP_DIR}; \
	chown ${DOCKER_USER}:www-data /var/www/${APP_DIR};


RUN wget https://rclone.org/install.sh && \
    chmod +x install.sh && \
    ./install.sh

USER ${DOCKER_USER}


# # Install all lib need
# RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
#     && apt-get install -y locales apt-utils git libicu-dev g++ libpng-dev \
#     libxml2-dev libzip-dev libonig-dev libxslt-dev gconf-service libasound2 \
#     libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 \
#     libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 \
#     libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 \
#     libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 \
#     libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libnss3 lsb-release \
#     xdg-utils wget libgbm-dev libxshmfence-dev \ 
#     && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# # install node
# COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
# COPY --from=node /usr/local/bin/node /usr/local/bin/node
# RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# # Install php-mysql driver and others
# RUN docker-php-ext-install pdo pdo_mysql gd opcache intl zip calendar dom mbstring zip gd xsl pcntl sockets xml

# # XDebug

# # RUN yes | pecl install xdebug \
# #     && docker-php-ext-enable xdebug \
# #     && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
# #     && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
# #     && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini \
# #     && echo "xdebug.idekey=XDEBUG" >> /usr/local/etc/php/conf.d/xdebug.ini \
# #     && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
# #     && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
# #     && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini

# WORKDIR /workspace

# RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
# php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"; \
# php composer-setup.php;\
# php -r "unlink('composer-setup.php');"; \
# mv composer.phar /usr/local/bin/composer

# RUN wget https://get.symfony.com/cli/installer -O - | bash; \
#     mv /root/.symfony5/bin/symfony /usr/local/bin/symfony