# R606_eval

## Installation
```bash
composer install
docker compose up  --build -d
```

## Migration
Se font automatiquement lors de l'accès à index.php

## Test
```bash
docker exec -w /app php_apache ./vendor/bin/phpunit --configuration /var/www/html/phpunit.xml
```

## Lint
```bash
docker exec -w /app php_apache ./vendor/bin/phpstan analyse --configuration phpstan.neon
```