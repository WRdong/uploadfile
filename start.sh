#!/bin/bash

#PHP ini
iniFile='/usr/local/etc/php/conf.d/docker-vars.ini'
#touch /usr/local/etc/php/conf.d/docker-vars.ini
echo '' > $iniFile

# Increase the memory_limit
echo "memory_limit = 256M" >> $iniFile

# Increase the post_max_size
echo "post_max_size = 200M" >> $iniFile

# Increase the upload_max_filesize
echo "upload_max_filesize = 200M" >> $iniFile

echo -e "\n"
echo 'opcache.enable=1' >> $iniFile
echo 'opcache.memory_consumption=256' >> $iniFile
echo 'opcache.revalidate_freq=3' >> $iniFile
echo 'opcache.interned_strings_buffer=16' >> $iniFile
echo 'opcache.max_accelerated_files=20000' >> $iniFile




# Increase the logs
#echo -e "error_reporting = E_ALL \nlog_errors = On \nerror_log = /var/www/logs/error.log" >> /usr/local/etc/php/conf.d/docker-vars.ini

# Disable phpredis extension
echo '' > /usr/local/etc/php/conf.d/docker-php-ext-redis.ini


#chown -Rf www-data:www-data /var/www/html/license
#chown -Rf www-data:www-data /var/www/html/basic/storage/*
#chown -Rf www-data:www-data /data/kd3000-system-service/storage
#chown -Rf www-data:www-data /var/www/html/basic/public/dy/*

# schedule
crond
#echo '* * * * * php /var/www/html/basic/artisan schedule:run >> /dev/null 2>&1' >  /etc/crontabs/root

#\cp -f /etc/supervisord.conf.example /etc/supervisord.conf

#supervisord -c /etc/supervisord.conf

#start php-fpm
php-fpm