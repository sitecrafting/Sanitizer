#!/usr/bin/env bash

# Update
apt-get update
apt-get upgrade

# Install Apache & PHP
apt-get install -y php5
apt-get install -y php5-mysql php5-curl php5-gd php5-intl php-pear php5-imap php5-mcrypt php5-recode php5-xmlrpc php5-xsl php5-fpm
apt-get install -y git

# Install composer
php -r "readfile('https://getcomposer.org/installer');" | php
mv composer.phar /usr/bin/composer

#Create the web root if it does not exist
mkdir -p /var/www

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
echo "Deploying basic Magento Database"
mysql -u root -ppassword sanitizer < /vagrant/sql/sanitizer_base.sql

echo "Deploying latest changes"
mysql -u root -ppassword sanitizer < /vagrant/sql/sanitizer.sql

#Updating the core_config_data web urls
echo "Updating the store URL's to use part 8080"

#Updating the URLs
mysql -u root -ppassword -e "USE sanitizer; UPDATE core_config_data SET value = 'http://sanitizer.dev:8080/' WHERE path IN ('web/unsecure/base_url', 'web/secure/base_url')"
