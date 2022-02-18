<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Mockery as m;

it('can create tables with checks', function() {
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
    ], $blueprint->toSql($connection, new MySqlGrammar));
});

it('can add checks to existing tables', function() {
    /** @var Connection $connection */
    $connection = m::mock(Connection::class);

    $blueprint = new Blueprint('users');
    $blueprint->check('age>21', 'min_age_check');

    $this->assertEquals([
        'alter table `users` add constraint `min_age_check` check (age>21)',
    ], $blueprint->toSql($connection, new MySqlGrammar));
});

it('can drop check constraints', function() {
    /** @var Connection $connection */
    $connection = m::mock(Connection::class);

    $base = new Blueprint('users');
    $base->dropCheck('min_age_check');

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table `users` drop constraint `min_age_check`',
    ], $blueprint->toSql($connection, new MySqlGrammar));
});

it('can drop multiple check constraints', function() {
    /** @var Connection $connection */
    $connection = m::mock(Connection::class);

    $base = new Blueprint('users');
    $base->dropCheck('min_age_check', 'max_age_check');

    $blueprint = clone $base;
    $this->assertEquals([
        'alter table `users` drop constraint `min_age_check`, drop constraint `max_age_check`',
    ], $blueprint->toSql($connection, new MySqlGrammar));
});
