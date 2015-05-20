#!/usr/bin/env bash

# Update
apt-get update
apt-get upgrade

# Install Apache & PHP
apt-get install -y apache2
apt-get install -y php5
apt-get install -y libapache2-mod-php5
apt-get install -y php5-mysql php5-curl php5-gd php5-intl php-pear php5-imap php5-mcrypt php5-recode php5-xmlrpc php5-xsl php5-fpm
apt-get install -y php5-memcache
apt-get install -y memcached
apt-get install -y git

# Install composer
php -r "readfile('https://getcomposer.org/installer');" | php
mv composer.phar /usr/bin/composer

#Create the web root if it does not exist
mkdir -p /var/www

# Apache vhosts
VHOST=$(cat <<EOF
    #Admin Vhost
    <VirtualHost *:80>
        DocumentRoot "/var/www"
        ServerName sanitizer.dev

        SetEnv  TT_IDENTIFIER           phil
        SetEnv  MAGE_RUN_CODE           sanitizer_default
        SetEnv  MAGE_RUN_TYPE           store
        SetEnv  MAGE_IS_DEVELOPER_MODE  0

        <Directory "/var/www">
            AllowOverride All
        </Directory>
    </VirtualHost>
EOF
)

echo "$VHOST" > /etc/apache2/sites-enabled/000-default.conf

a2enmod rewrite
service apache2 restart

# Mysql
#
# Ignore the post install questions
export DEBIAN_FRONTEND=noninteractive
# Install MySQL quietly
apt-get -q -y install mysql-server-5.5

mysql -u root -e "CREATE DATABASE IF NOT EXISTS sanitizer"
mysql -u root -e "GRANT ALL PRIVILEGES ON sanitizer.* TO 'root'@'localhost' IDENTIFIED BY 'password'; FLUSH PRIVILEGES;"

#Load the database and host
echo "Installing database"
echo "Deploying basic COAST Magento Database"
mysql -u root -ppassword sanitizer < /vagrant/sql/sanitizer_base.sql

echo "Deploying latest changes"
mysql -u root -ppassword sanitizer < /vagrant/sql/sanitizer.sql

#Updating the core_config_data web urls
echo "Updating the store URL's to use part 8080"

#Updating the URLs
mysql -u root -ppassword -e "USE sanitizer; UPDATE core_config_data SET value = 'http://sanitizer.dev:8080/' WHERE value = 'http://www.phoneshopbysainsburys.co.uk/'";
mysql -u root -ppassword -e "USE sanitizer; UPDATE core_config_data SET value = 'http://sanitizer.dev:8080/' WHERE value = 'https://sainsburysadmin.2020mobile.com/'";
mysql -u root -ppassword -e "USE sanitizer; UPDATE core_config_data SET value = 'http://sanitizer.dev:8080/' WHERE value LIKE '%sanitizer.dev%' AND path LIKE '%base_url%'";

memcached -d -m 256 -l 127.0.0.1 -p 11211