# mes2camp

Base de données qualité de l'air des campagnes de mesure.

## Installation

Install all required libs found in requirements.txt with the correct files path and names.
 
Create the file ```src/web_app/scripts/config.php``` with the following code
```php
<?php
// Variables pour la connexion à PostgreSQL
$pg_host = '';
$pg_lgn = '';
$pg_pwd = '';
$pg_db = 'campagne';
?>
```

### Config with nginx

nginx installed

Install php and it's modules

```bash
sudo apt-get install php7.0-cli
sudo apt-get install php7.0-fpm
sudo apt-get install php7.0-pgsql
service php7.0-fpm status
```

In ```/etc/php/7.0/fpm/pool.d/www.conf``` uncomment the following:

```bash
listen.allowed_clients = 127.0.0.1
```

In ```/etc/php/7.0/fpm/pool.d/www.conf``` comment the following:

```bash
listen = /var/run/php5-fpm.sock
```

In ```/etc/php/7.0/fpm/pool.d/www.conf``` add the following:

```bash
listen = 9000
```

restart php7.0-fpm

```bash
sudo service php7.0-fpm status
```

Create an nginx conf file as follow

```bash
worker_processes  1;

events {
    worker_connections  1024;
}

http {
    include       mime.types;
    default_type  application/octet-stream;

    sendfile        on;

    keepalive_timeout  65;

    server {
        listen       80;
        server_name  localhost;

        location / {
            root   /home/rhum/projets/mes2camp/src/web_app;
            index  index.html index.htm index.php;
        }   

        location /scripts {
            root /srcipts;
        }

        # pass the PHP scripts to FastCGI server listening on 127.0.0.1:9000
        #
        location ~ \.php$ {
            root           /home/rhum/projets/mes2camp/src/web_app;
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  /home/rhum/projets/mes2camp/src/web_app/$fastcgi_script_name;
            include        fastcgi_params;  
        }   

    }
}

```

Reload nginx

The web server needs CORS configuration.

Point to the following adress: http://www.[yourhost]/mes2camp/src/web_app

## Base de données tests 

## API Reference

## Tests

## Contributors

Benjamin Rocher, Romain Souweine

## License

License MIT

