## Laravel Scaffolding

Package with some console commands for code generation.

### Installation
```shell
composer require amgrade/laravel-scaffolding
```

If you prefer manual installation, then add to `config/app.php` into `providers` section next line:

```php
'providers' => [
    AMgrade\Scaffolding\ScaffoldingServiceProvider::class,
],
```

Otherwise, it will be autodiscovered.

### List of commands

- `php artisan make:observer` — adds suffix "Observer" and autoregister observer class in EventsServiceProvider.php
- `php artisan make:console` — adds suffix "Command" and autoregister console command class in Kernel.php
- `php artisan make:repository` — adds an ability to scaffold whole structure for repository pattern and creates given repository class
