FROM nginx:1.17
MAINTAINER apps@tiltshift.nl


ARG DEBIAN_FRONTEND=noninteractive
ENV GOOGLEMAPS_API_KEY insecure
ENV TOURBUZZ_URI_PROTOCOL https://
ENV TOURBUZZ_URI tourbuzz.tiltshiftapps.nl
ENV TOURBUZZ_API_URI_PROTOCOL https://
ENV TOURBUZZ_API_URI tourbuzz-api.tiltshiftapps.nl
ENV TOURBUZZ_RECIPIENTS mike@example.com, jane@example.com
ENV TOURINGCAR_PROTOCOL https://
ENV TOURINGCAR_URI www.example.com/touringcars
ENV TOURINGCAR_CONTACT_NAME Mike Doe
ENV TOURINGCAR_CONTACT_EMAIL mike@example.com

EXPOSE 80

# install php packages
RUN apt-get update \
 && apt-get install -yq apt-transport-https ca-certificates \
 && apt update
 
RUN apt-get update && apt-get -y install git wget cron rsync curl unzip \
  php-fpm \
  php-intl \
  php-pgsql \
  php-curl \
  php-cli \
  php-gd \ 
  php-mbstring \
  # php-mcrypt \ DEPRECATED
  php-opcache \
  php-sqlite3 \
  php-xml \
  php-xsl \
  php-zip \
  php-igbinary \
  php-json \
  php-memcached \
  php-msgpack \
  php-xmlrpc \
 && apt-get -y upgrade \
 && apt-get clean

# create basic directory
RUN mkdir -p /srv/web/tourbuzz

# project setup
COPY . /srv/web/tourbuzz
WORKDIR /srv/web
RUN wget https://getcomposer.org/composer.phar
# nginx and php setup
COPY Docker/tourbuzz.vhost /etc/nginx/conf.d/tourbuzz.vhost.conf
RUN rm /etc/nginx/conf.d/default.conf \
  && sed -i '/\;listen\.mode\ \=\ 0660/c\listen\.mode=0666' /etc/php/7.3/fpm/pool.d/www.conf \
  && sed -i '/pm.max_children = 5/c\pm.max_children = 20' /etc/php/7.3/fpm/pool.d/www.conf \
  && sed -i '/\;pm\.max_requests\ \=\ 500/c\pm\.max_requests\ \=\ 100' /etc/php/7.3/fpm/pool.d/www.conf \
  && echo "server_tokens off;" > /etc/nginx/conf.d/extra.conf \
  && echo "client_max_body_size 20m;" >> /etc/nginx/conf.d/extra.conf \
  && sed -i '/upload_max_filesize \= 2M/c\upload_max_filesize \= 20M' /etc/php/7.3/fpm/php.ini \
  && sed -i '/post_max_size \= 8M/c\post_max_size \= 21M' /etc/php/7.3/fpm/php.ini \
  && sed -i "/variables_order \=/c\variables_order = \"EGPCS\"" /etc/php/7.3/fpm/php.ini \
  && sed -i '/\;date\.timezone \=/c\date.timezone = Europe\/Amsterdam' /etc/php/7.3/fpm/php.ini \
  && sed -i '/\;security\.limit_extensions \= \.php \.php3 \.php4 \.php5 \.php7/c\security\.limit_extensions \= \.php' /etc/php/7.3/fpm/pool.d/www.conf \
  && sed -e 's/;clear_env = no/clear_env = no/' -i /etc/php/7.3/fpm/pool.d/www.conf

# only install dependencies
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN php composer.phar install -d tourbuzz/tourbuzz/ --prefer-dist --no-progress --no-scripts

# run
COPY Docker/docker-entrypoint.sh /docker-entrypoint.sh
COPY Docker/config.php /srv/web/tourbuzz/tourbuzz/app/config/
RUN chmod +x /docker-entrypoint.sh
# RUN cat /etc/php/7.3/fpm/php.ini | grep variables_order
CMD /docker-entrypoint.sh
