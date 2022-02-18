# Add check constraints to your Laravel schema

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ditscheri/laravel-check-constraints.svg?style=flat-square)](https://packagist.org/packages/ditscheri/laravel-check-constraints)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ditscheri/laravel-check-constraints/run-tests?label=tests)](https://github.com/ditscheri/laravel-check-constraints/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ditscheri/laravel-check-constraints/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ditscheri/laravel-check-constraints/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ditscheri/laravel-check-constraints.svg?style=flat-square)](https://packagist.org/packages/ditscheri/laravel-check-constraints)

This packages adds macros to the schema builder, which allow you to add check constraints to your databse tables. Currently, the package only supports the MySQL driver.

## Installation

You can install the package via composer:

```bash
composer require ditscheri/laravel-check-constraints
```


You can publish the config file with:

```bash
php artisan vendor:publish --tag="check-constraints-config"
```

This is the contents of the published config file:

```php
return [
    'sqlite' => [
        'throw' => true,
    ],
];
```

Since SQLite comes with a number of limitations, this package currently does not support SQLite at all. You can use the abvove configuration to decide wether to throw a `RuntimeException` when used with SQLite or wether to fail silently.

If you only use SQLite in your tests, you might be fine with setting the option to `false`.

## Usage

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('age');

    // This is what the package allows you to do:
    $table->check('age >= 21');
});
```

Now you have an additional layer of integrity checks within your database. If you try to insert or update a row, that violates the checks, an `\Illuminate\Database\QueryException` will be thrown:

```php
// This is fine:
User::create(['age' => 30]); 

// This is not:
User::create(['age' => 18]); 
/* 
Illuminate\Database\QueryException with message
SQLSTATE[HY000]: General error: 3819 
Check constraint 'users_age_21_check' is violated.
*/
```

Typical use cases for checks are date ranges, where the end date may not be before the start date, or prices with discounts:

```php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->datetime('starts_at');
    $table->datetime('ends_at');
    $table->unsignedInteger('price');
    $table->unsignedInteger('discounted_price');

    // Ensure that date ranges are valid (may not end before it even started)
    $table->check('starts_at <= ends_at');
    // Ensure that discounts are lower than the regular price:
    $table->check('discounted_price <= price', 'check_valid_discounts');
});
```

Of course you will still want to validate your data and detect such things inside of your application code before even reaching out to the database. But sometimes it is useful, to add additional integrity checks right on the database layer itself. 

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Daniel Bakan](https://github.com/dbakan)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
