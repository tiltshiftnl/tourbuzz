FROM nginx:1.16.0
MAINTAINER datapunt@amsterdam.nl

EXPOSE 80

# install php packages
RUN apt-get update && apt-get install -y git vim wget cron rsync php7.0-fpm php7.0-intl php7.0-pgsql php7.0-curl php7.0-cli php7.0-gd php7.0-intl php7.0-mbstring php7.0-mcrypt php7.0-opcache php7.0-sqlite3 php7.0-xml php7.0-xsl php7.0-zip php7.0-igbinary php7.0-json php7.0-memcached php7.0-msgpack php7.0-xmlrpc \
  && apt-get -y upgrade && apt-get -y dist-upgrade && apt-get autoremove && apt-get check && apt-get clean

# project setup
COPY . /srv/web/tourbuzz
WORKDIR /srv/web

# nginx and php setup
COPY Docker/tourbuzz.vhost /etc/nginx/conf.d/tourbuzz.vhost.conf
RUN wget https://getcomposer.org/composer.phar \
  && php composer.phar install -d tourbuzz/tourbuzz \
  && rm /etc/nginx/conf.d/default.conf \
  && sed -i '/\;listen\.mode\ \=\ 0660/c\listen\.mode=0666' /etc/php/7.0/fpm/pool.d/www.conf \
  && sed -i '/pm.max_children = 5/c\pm.max_children = 20' /etc/php/7.0/fpm/pool.d/www.conf \
  && sed -i '/\;pm\.max_requests\ \=\ 500/c\pm\.max_requests\ \=\ 100' /etc/php/7.0/fpm/pool.d/www.conf \
  && echo "server_tokens off;" > /etc/nginx/conf.d/extra.conf \
  && echo "client_max_body_size 20m;" >> /etc/nginx/conf.d/extra.conf \
  && sed -i '/upload_max_filesize \= 2M/c\upload_max_filesize \= 20M' /etc/php/7.0/fpm/php.ini \
  && sed -i '/post_max_size \= 8M/c\post_max_size \= 21M' /etc/php/7.0/fpm/php.ini \
  && sed -i '/\;date\.timezone \=/c\date.timezone = Europe\/Amsterdam' /etc/php/7.0/fpm/php.ini \
  && sed -i '/\;security\.limit_extensions \= \.php \.php3 \.php4 \.php5 \.php7/c\security\.limit_extensions \= \.php' /etc/php/7.0/fpm/pool.d/www.conf \
  && sed -e 's/;clear_env = no/clear_env = no/' -i /etc/php/7.0/fpm/pool.d/www.conf

COPY Docker/docker-entrypoint.sh /docker-entrypoint.sh
COPY Docker/config.php /srv/web/tourbuzz/tourbuzz/app/config/
RUN chmod +x /docker-entrypoint.sh
CMD /docker-entrypoint.sh
