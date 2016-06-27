FROM php:5.6-apache
 
# Workaround for write permission on write to MacOS X volumes
# See https://github.com/boot2docker/boot2docker/pull/534
RUN usermod -u 1000 www-data

 
# Enable Apache mod_rewrite
RUN a2enmod rewrite

RUN apt-get update
RUN apt-get install -y git
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /bin/composer
RUN chmod a+x /bin/composer
COPY ./twiginit /var/www/html
WORKDIR /var/www/html
RUN composer install