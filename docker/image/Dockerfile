#
# PHP_VERSION 8.1
#
FROM debian:11

RUN echo "Europe/Paris" > /etc/timezone

# PHP version
ENV PHP_VERSION 8.1

# dependencies required for running "phpize"
# (see persistent deps below)
ENV PHPIZE_DEPS \
        autoconf \
        dpkg-dev \
        file \
        g++ \
        gcc \
        libc-dev \
        make \
        pkg-config \
        re2c

RUN apt-get update && apt-get install -y \
    apt-transport-https \
    bat \
    ca-certificates \
    cron \
    curl \    
    git \
    iputils-ping \
    lsb-release \
    $PHPIZE_DEPS \
    sudo \
    python \
    unzip \
    wget \
    vim \
    xz-utils \
    zsh

RUN sh -c "$(curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh)"

RUN wget -O- https://packages.sury.org/php/apt.gpg | apt-key add - && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

RUN apt-get update && apt-get install -y \
  apache2 \
  php$PHP_VERSION \
  libapache2-mod-php$PHP_VERSION \
  php$PHP_VERSION-mysql \
  php$PHP_VERSION-sqlite \
  php$PHP_VERSION-curl \
  php$PHP_VERSION-opcache \
  php$PHP_VERSION-cli \
  php$PHP_VERSION-intl \
  php$PHP_VERSION-common \
  php$PHP_VERSION-memcached \
  php$PHP_VERSION-xml \
  php$PHP_VERSION-zip \
  php$PHP_VERSION-mbstring \
  php$PHP_VERSION-xdebug \
  php$PHP_VERSION-soap \
  php$PHP_VERSION-apcu

#RUN apt-get install -y php$PHP_VERSION-apcu

# composer install
RUN curl -sS https://getcomposer.org/installer | php
RUN sudo mv composer.phar /usr/local/bin/composer
RUN sudo chmod +x /usr/local/bin/composer


# Creating the user and group
RUN groupadd user && useradd -g user -m -d /home/user user -s /bin/bash

#clean apt lib
RUN rm -r /var/lib/apt/lists/*


# apache config
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
RUN a2enmod rewrite headers expires deflate setenvif mpm_prefork
RUN a2dismod mpm_event
EXPOSE 80
EXPOSE 443

# config batch log folder
RUN mkdir -p /var/log/batch
RUN chmod 777 /var/log/batch

COPY ./run.sh /run.sh
RUN chmod 777 /run.sh
CMD ["/run.sh"]
