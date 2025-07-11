/*     20161125      */
/*********************/
/*INSTALLING MOSAICO */ 
/*********************/
This is the official url and doc.

URL: https://github.com/voidlabs/mosaico

* If you are using Ubuntu 14 with the default nodejs distro version (nodejs should be greater than v6)

$ sudo apt-get purge nodejs npm

$ curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -

$ sudo apt-get install -y nodejs


cd /var/www/admin2/phive_admin/plugins/mosaico &&
npm install &&
npm install -g grunt-cli &&
grunt

/*********************/
/*      GRUNT        */
/*********************/
Grunt is used to build everything / creation of folder that Mosaico
needs to run. 
For some reason in test2 jshint is throwing a lot of errors / warnings.
If it happens use this sintax
> grunt --force

> cd /var/www/admin2/phive_admin/plugins/mosaico-template/
> mkdir email-templates
> chown www-data email-templates/ -R
> cd email-templates
> mkdir thumbnail
> sudo chown www-data thumbnail/ -R
> mkdir static
> sudo chown www-data static/ -R

IF SOME CSS FILE ARE MISSING WE CAN USE THIS SCP COMMAND TO SEND THESE MISSING F.
> scp *.css root@88.208.221.127:/var/www/admin2/phive_admin/plugins/mosaico/dist


/*********************/
/*    IMAGEMAGICK    */
/*********************/
Mosaico requires imagemagick.
In order to get everything running needs to be installed 
if is not already installed. 
There are 2 cases:

1) PHP 5 - sudo apt-get install php5-imagick
sudo php5enmod imagick

2) PHP 7 - sudo apt-get install php-imagick
sudo service php7.0-fpm reload
