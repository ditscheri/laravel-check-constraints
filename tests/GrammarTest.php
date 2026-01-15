<?php


it('can create tables with checks', function () {
    $blueprint = getBlueprint('MySql', table: 'users');
    $blueprint->create();
    $blueprint->unsignedInteger('age');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals(
        'alter table `users` add constraint `min_age_check` check (age>21)',
        $blueprint->toSql()[1],
    );

    $blueprint = getBlueprint('Postgres', table: 'users');
    $blueprint->create();
    $blueprint->unsignedInteger('age');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals(
        'alter table "users" add constraint "min_age_check" check (age>21)',
        $blueprint->toSql()[1],
    );

    $blueprint = getBlueprint('SqlServer', table: 'users');
    $blueprint->create();
    $blueprint->unsignedInteger('age');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals(
        'alter table "users" add constraint "min_age_check" check (age>21)',
        $blueprint->toSql()[1],
    );
});

it('can add checks to existing tables', function () {
    $blueprint = getBlueprint('MySql', table: 'users');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        'alter table `users` add constraint `min_age_check` check (age>21)',
    ], $blueprint->toSql());

    $blueprint = getBlueprint('Postgres', table: 'users');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        'alter table "users" add constraint "min_age_check" check (age>21)',
    ], $blueprint->toSql());

    $blueprint = getBlueprint('SqlServer', table: 'users');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        'alter table "users" add constraint "min_age_check" check (age>21)',
    ], $blueprint->toSql());
});

it('can drop check constraints', function () {
    $blueprint = getBlueprint('MySql', table: 'users');
    $blueprint->dropCheck('min_age_check');
    $this->assertEquals([
        'alter table `users` drop constraint `min_age_check`',
    ], $blueprint->toSql());

    $blueprint = getBlueprint('Postgres', table: 'users');
    $blueprint->dropCheck('min_age_check');
    $this->assertEquals([
        'alter table "users" drop constraint "min_age_check"',
    ], $blueprint->toSql());

    $blueprint = getBlueprint('SqlServer', table: 'users');
    $blueprint->dropCheck('min_age_check');
    $this->assertEquals([
        'alter table "users" drop constraint "min_age_check"',
    ], $blueprint->toSql());
});

it('can drop multiple check constraints', function () {
    $blueprint = getBlueprint('MySql', table: 'users');
    $blueprint->dropCheck('min_age_check', 'max_age_check');
    $this->assertEquals([
        'alter table `users` drop constraint `min_age_check`, drop constraint `max_age_check`',
    ], $blueprint->toSql());

    $blueprint = getBlueprint('Postgres', table: 'users');
    $blueprint->dropCheck('min_age_check', 'max_age_check');
    $this->assertEquals([
        'alter table "users" drop constraint "min_age_check", drop constraint "max_age_check"',
    ], $blueprint->toSql());

    $blueprint = getBlueprint('SqlServer', table: 'users');
    $blueprint->dropCheck('min_age_check', 'max_age_check');
    $this->assertEquals([
        'alter table "users" drop constraint "min_age_check", drop constraint "max_age_check"',
    ], $blueprint->toSql());
});

it('throws exception for SQLite for create table', function () {
    config()->set('check-constraints.sqlite.throw', true);

    $base = getBlueprint('SQLite', table: 'users');
    $base->create();
    $base->check('age>21', 'min_age_check');

    $this->expectException(RuntimeException::class);

    $base->toSql();
});

it('throws exception for SQLite for alter table', function () {
    config()->set('check-constraints.sqlite.throw', true);

    $base = getBlueprint('SQLite', table: 'users');
    $base->check('age>21', 'min_age_check');

    $this->expectException(RuntimeException::class);

    $base->toSql();
});

it('throws exception for SQLite for dropCheck', function () {
    config()->set('check-constraints.sqlite.throw', true);

    $base = getBlueprint('SQLite', table: 'users');
    $base->dropCheck('min_age_check');

    $this->expectException(RuntimeException::class);

    $base->toSql();
});

it('can fail silently for SQLite via config', function () {
    config()->set('check-constraints.sqlite.throw', false);

    // create table with check:
    $blueprint = getBlueprint('SQLite', table: 'users');
    $blueprint->create();
    $blueprint->unsignedInteger('age');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        'create table "users" ("age" integer not null)',
    ], $blueprint->toSql());

    // alter table with check and column:
    $blueprint = getBlueprint('SQLite', table: 'users');
    $blueprint->unsignedInteger('age');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        'alter table "users" add column "age" integer not null',
    ], $blueprint->toSql());

    // alter table with check only:
    $blueprint = getBlueprint('SQLite', table: 'users');
    $blueprint->check('age>21', 'min_age_check');
    $this->assertEquals([
        // empty array
    ], $blueprint->toSql());

    // dropCheck:
    $blueprint = getBlueprint('SQLite', table: 'users');
    $blueprint->dropCheck('min_age_check');
    $this->assertEquals([
        // empty array
    ], $blueprint->toSql());
});
