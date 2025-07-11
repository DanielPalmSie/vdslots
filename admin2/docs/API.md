

** Introduction **

BO project has its own api, only loading api controllers and with early event authentication through an API key.

Example: 

curl --header "X-BO-KEY: dev2" http://boapi.loc/api/test/

** Api Auth **

Api keys on test and dev environments are located in config files.

On production environments, will get the key from the .env file.

If api key is not configured, then you cannot authenticate.


** Webserver configuration **

- NGINX minimum config (tested): see nginx-boapi.conf.example
- Apache minimum config (not tested): see apache-boapi.conf.example and put it into .htaccess
