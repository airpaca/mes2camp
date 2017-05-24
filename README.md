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

The web server needs CORS configuration.

Point to the following adress: http://www.[yourhost]/mes2camp/src/web_app

## Base de données tests 

## API Reference

## Tests

## Contributors

Benjamin Rocher, Romain Souweine

## License

License MIT
