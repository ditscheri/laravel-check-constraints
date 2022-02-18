<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Mockery as m;

it('can create tables with checks', function () {
    $connection = m::mock(Connection::class);
    $connection->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
    $connection->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
    $connection->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

    /** @var Connection $connection */

    $base = new Blueprint('users');
    $base->create();
    $base->unsignedInteger('age');
    $base->check('age>21', 'min_age_check');

    $blueprint = clone $base;
    $this->assertEquals([
        'create table `users` (`age` int unsigned not null) default character set utf8 collate \'utf8_unicode_ci\'',
        'alter table `users` add constraint `min_age_check` check (age>21)',
    ], $blueprint->toSql($connection, new MySqlGrammar()));

    $blueprint = clone $base;
    $this->assertEquals([
        'create table "users" ("age" integer not null)',
        'alter table "users" add constraint "min_age_check" check (age>21)',
    ], $blueprint->toSql($connection, new PostgresGrammar()));

    $blueprint = clone $base;
    $this->assertEquals([
        'create table "users" ("age" int not null)',
        'alter table "users" add constraint "min_age_check" check (age>21)',
    ], $blueprint->toSql($connection, new SqlServerGrammar()));
});

it('can add checks to existing tables', function () {
    /** @var Connection $connection */
    $connection = m::mock(Connection::class);

    $base = new Blueprint('users');
    $base->check('age>21', 'min_age_check');

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table `users` add constraint `min_age_check` check (age>21)',
    ], $blueprint->toSql($connection, new MySqlGrammar()));

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table "users" add constraint "min_age_check" check (age>21)',
    ], $blueprint->toSql($connection, new PostgresGrammar()));

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table "users" add constraint "min_age_check" check (age>21)',
    ], $blueprint->toSql($connection, new SqlServerGrammar()));
});

it('can drop check constraints', function () {
    /** @var Connection $connection */
    $connection = m::mock(Connection::class);

    $base = new Blueprint('users');
    $base->dropCheck('min_age_check');

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table `users` drop constraint `min_age_check`',
    ], $blueprint->toSql($connection, new MySqlGrammar()));

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table "users" drop constraint "min_age_check"',
    ], $blueprint->toSql($connection, new PostgresGrammar()));

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table "users" drop constraint "min_age_check"',
    ], $blueprint->toSql($connection, new SqlServerGrammar()));
});

it('can drop multiple check constraints', function () {
    /** @var Connection $connection */
    $connection = m::mock(Connection::class);

    $base = new Blueprint('users');
    $base->dropCheck('min_age_check', 'max_age_check');

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table `users` drop constraint `min_age_check`, drop constraint `max_age_check`',
    ], $blueprint->toSql($connection, new MySqlGrammar()));

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table "users" drop constraint "min_age_check", drop constraint "max_age_check"',
    ], $blueprint->toSql($connection, new PostgresGrammar()));

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table "users" drop constraint "min_age_check", drop constraint "max_age_check"',
    ], $blueprint->toSql($connection, new SqlServerGrammar()));
});

it('throws exception for SQLite for create table', function () {
    config()->set('check-constraints.sqlite.throw', true);

    $connection = m::mock(Connection::class);

    $base = new Blueprint('users');
    $base->create();
    $base->check('age>21', 'min_age_check');

    $this->expectException(RuntimeException::class);

    $base->toSql($connection, new SQLiteGrammar());
});

it('throws exception for SQLite for alter table', function () {
    config()->set('check-constraints.sqlite.throw', true);

    $connection = m::mock(Connection::class);

    $base = new Blueprint('users');
    $base->check('age>21', 'min_age_check');

    $this->expectException(RuntimeException::class);

    $base->toSql($connection, new SQLiteGrammar());
});

it('throws exception for SQLite for dropCheck', function () {
    config()->set('check-constraints.sqlite.throw', true);

    $connection = m::mock(Connection::class);

    $base = new Blueprint('users');
    $base->dropCheck('min_age_check');

    $this->expectException(RuntimeException::class);

    $base->toSql($connection, new SQLiteGrammar());
});

it('can fail silently for SQLite via config', function () {
    config()->set('check-constraints.sqlite.throw', false);

    $connection = m::mock(Connection::class);

    // create table with check:
    $blueprint = new Blueprint('users');
    $blueprint->create();
    $blueprint->unsignedInteger('age');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        'create table "users" ("age" integer not null)',
    ], $blueprint->toSql($connection, new SQLiteGrammar()));

    // alter table with check and column:
    $blueprint = new Blueprint('users');
    $blueprint->unsignedInteger('age');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        'alter table "users" add column "age" integer not null',
    ], $blueprint->toSql($connection, new SQLiteGrammar()));

    // alter table with check only:
    $blueprint = new Blueprint('users');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        // empty array
    ], $blueprint->toSql($connection, new SQLiteGrammar()));

    // dropCheck:
    $blueprint = new Blueprint('users');
    $blueprint->dropCheck('min_age_check');
    $this->assertEquals([
        // empty array
    ], $blueprint->toSql($connection, new SQLiteGrammar()));
});
