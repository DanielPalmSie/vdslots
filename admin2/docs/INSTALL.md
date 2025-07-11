**First deployment**

CHANGELOG. 2017-06-27: Removed 2 steps as config files and folders are automatically created now (see composer.json).

NOTE: assuming that you install the project under /var/www/admin2 and old project is under /var/www/videoslots

1. Clone the videoslots2 mercurial project into a folder (i.e. admin2): git clone git@gitlab.videoslots.com:backoffice/videoslots2.git admin2

2. If composer is not installed on the box, install it globally
    $ curl -sS https://getcomposer.org/installer | php
    $ mv composer.phar /usr/local/bin/composer

3. Get necessary PHP packages via composer.
    Into the project folder you need to execute:
    $ composer install

4. Create new page for the application. The alias must be the parametre as in the .env file BO_BASE_URL, i.e. admin2

   - Go to edit pages section on the old BO
   - New page
     - Alias: admin2
     - Filename: diamondbet/admin.php
     - Parent: / (root)

5. Create admin.php under videoslots/diamondbet in the current videoslots application
with the following content:

`<?php
require_once __DIR__ . '/../phive/admin.php';
// boot silex
require_once '/var/www/admin2/public/index.php';`

7. Create symlink for assets, js, css and additional images
    $ ln -s /var/www/admin2/phive_admin/ /var/www/videoslots/phive/admin

Create a file_uploads directory and create a symlink for events images
    $ mkdir -p /var/www/admin2/file_uploads
    $ ln -s /var/www/videoslots/file_uploads/events /var/www/admin2/file_uploads/events



**Updating from v1 to v2**

Composer update is required to be able to work with this version. Clean up of the views folder is also required, but it is automatically
done with a post-update script on Composer.

NOTE: in case you want to go back to the previous version, views folder needs to be deleted manually.

Admin2 project works now with Silex 2, Laravel 5.4 and it requires PHP >= 5.6.

- Silex guide regarding major changes: https://github.com/silexphp/Silex/wiki/Upgrading-Silex-1.x-to-2.x

- Laravel 5.4 upgrade guide: https://laravel.com/docs/5.4/upgrade
