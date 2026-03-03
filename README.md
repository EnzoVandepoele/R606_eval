# R606_eval

## Installation
```bash
cd src/
composer install
docker compose up  --build -d
```

## Accès
Page d'accueil : http://localhost:8080/

## Migration
Se font automatiquement lors de l'accès à index.php

## Test PHPUnit
```bash
docker exec -w /var/www/html php_apache ./vendor/bin/phpunit
```

## Lint PHPStan
```bash
docker exec -w /app php_apache ./src/vendor/bin/phpstan analyse --configuration phpstan.neon
```