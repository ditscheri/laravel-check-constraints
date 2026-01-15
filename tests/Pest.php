<?php

use Ditscheri\CheckConstraints\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Mockery as m;

uses(TestCase::class)->in(__DIR__);

function getConnection(?string $grammar = null, string $prefix = '')
{
    $connection = m::mock(Connection::class)
        ->shouldReceive('getTablePrefix')->andReturn($prefix)
        ->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true)
        ->getMock();

    $grammar ??= 'MySql';
    $grammarClass = 'Illuminate\Database\Schema\Grammars\\'.$grammar.'Grammar';
    $builderClass = 'Illuminate\Database\Schema\\'.$grammar.'Builder';

    $connection->shouldReceive('getSchemaGrammar')->andReturn(new $grammarClass($connection));
    $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock($builderClass));
    $connection->shouldReceive('getConfig')->andReturnNull();

    if ($grammar === 'SQLite') {
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');
    }

    if ($grammar === 'MySql') {
        $connection->shouldReceive('isMaria')->andReturn(false);
    }

    return $connection;
}

function getBlueprint(
    ?string $grammar = null,
    string $table = '',
    ?Closure $callback = null,
    string $prefix = ''
): Blueprint {
    $connection = getConnection($grammar, $prefix);

    return new Blueprint($connection, $table, $callback);
}
