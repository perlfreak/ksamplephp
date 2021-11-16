KSamplePHP
====

Overview

Sample PHP App for a small project.

## Description

* For small scale.
* It is not a framework. (It is not an MVC model.)
* Stuff as much as possible into one file.
* Do not use object orientation as much as possible.
* Because it is elementary PHP, learning cost is low. And surely good performance.
* It can support multiple languages.
* It can manage authority.
* It can support multiple databases.

## Verification environment

Ubuntu 18.04.5 LTS
Apache 2.4.29
PHP 7.2.24
MariaDB 10.1.48
Redis 4.0.9  
Postfix 3.3.0

Bootstrap 5.0.2

## Install

Deploy the downloaded file to Apache's document root.  
ex. /var/www/html/ksamplephp/

Run init.sql as the root user of MariaDB.  
ex. mysql -u root -p[password] < init.sql

Place access.conf in the Apache configuration directory and restart the Apache service.  
ex. /etc/apache2/conf-available/access.conf  
    sudo systemctl restart apache2.service

Edit php.ini.  
ex. /etc/php/7.2/apache2/php.ini

Delete the next files from the expanded directory.  
access.conf  
init.sql  
php.ini-sample  
README.ja.md  
README.md

## Licence

[MIT](https://opensource.org/licenses/mit-license.html)

## Author

[perlfreak](https://github.com/perlfreak)
