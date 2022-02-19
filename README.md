# Add check constraints to your Laravel schema

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ditscheri/laravel-check-constraints.svg?style=flat-square)](https://packagist.org/packages/ditscheri/laravel-check-constraints)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ditscheri/laravel-check-constraints/run-tests?label=tests)](https://github.com/ditscheri/laravel-check-constraints/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ditscheri/laravel-check-constraints/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ditscheri/laravel-check-constraints/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ditscheri/laravel-check-constraints.svg?style=flat-square)](https://packagist.org/packages/ditscheri/laravel-check-constraints)

This packages allows you to add native check constraints to your database tables. 

You can read more about check constraints in the official documentations of [MySQL](https://dev.mysql.com/doc/refman/8.0/en/create-table-check-constraints.html), [PostrgeSQL](https://www.postgresql.org/docs/14/ddl-constraints.html), [SQLite](https://www.sqlite.org/lang_createtable.html#check_constraints) and [SQL Server](https://docs.microsoft.com/en-us/sql/relational-databases/tables/unique-constraints-and-check-constraints?view=sql-server-ver15#Check).

Currently, this package does not add check constraints to the SQLite driver, but you should be fine if you only use SQLite for running tests (see below).

## Installation

You can install the package via composer:

```bash
composer require ditscheri/laravel-check-constraints
```

## Usage

```php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->datetime('starts_at');
    $table->datetime('ends_at');

    // This is the new part:
    $table->check('starts_at < ends_at');
});
```

That last statement will produce the following SQL:
```sql
alter table `events` add constraint `events_starts_at_ends_at_check` check (starts_at < ends_at);
```

Now your database will only allow inserts and updates for rows with a valid date range. 

If you try to insert or update a row that violates the check, an `\Illuminate\Database\QueryException` will be thrown:

```php
Event::first()->update([
    'starts_at' => '2022-02-19 20:00:00',
    'end_at'    => '2022-02-19 18:00:00', // this one would be over before it even started?!
]); 
 
// Illuminate\Database\QueryException with message
// SQLSTATE[HY000]: General error: 3819 
// Check constraint 'events_starts_at_ends_at_check' is violated.
```

Another simple yet typical use case is with prices and discounts:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedInteger('price');
    $table->unsignedInteger('discounted_price');

    // Ensure that discounts are lower than the regular price:
    $table->check('discounted_price <= price');
});
```

Of course you will still want to validate your data within the application code and detect such things before even reaching out to the database. But sometimes it is useful to have an additional layer of integrity checks right in your database itself. 

Especially when you *read* data back from your database, your code may now safely assume that all the defined checks are guaranteed.

You can also add checks to existing tables:
```php
Schema::table('users', function (Blueprint $table) {
    $table->check('age > 18');
});
```

Use the second parameter for an optional custom constraint name:
```php
Schema::table('users', function (Blueprint $table) {
    $table->check('age > 18', 'require_min_age');
    $table->check('is_admin=1 OR company_id IS NOT NULL', 'non_admins_require_company');
});
```

You can drop check constraints by their name:
```php
Schema::table('users', function (Blueprint $table) {
    $table->dropCheck('require_min_age');
});
```

## A note about SQLite

While SQLite does support check constraints within `create table` statements, there are a number of limitions:

- SQLite cannot add check constraints to existing tables.
- SQLite cannot drop check constraints.

Since this package only relies on macros, it currently does not support the SQLite driver at all.

Instead, you can use the config `check-constraints.sqlite.throw` to define wether to throw a `RuntimeException` or to fail silently when using SQLite.

If you only use SQLite in your tests, you might be fine with setting the option to `false`. This gives you all the benefits of check constraints for your production environment, while your tests can still run using SQLite, where the calls to `$table->check()` will just be skipped.

## Configuration 

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
