## Local server
```aiignore
symfony server:start
```

```aiignore
php -S localhost:8000 -t public
```

## Database
```aiignore
php bin/console doctrine:database:create
```

```aiignore
php bin/console make:migration
```

```aiignore
php bin/console doctrine:migrations:migrate
```

```aiignore
php bin/console doctrine:database:drop --force
```

```aiignore
php bin/console doctrine:migrations:sync-metadata-storage
```