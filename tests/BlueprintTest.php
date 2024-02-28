<?php

use BenSampo\Enum\Enum;
use Illuminate\Database\Schema\Blueprint;

it('can generate default names', function () {
    collect([
        'date_end>=date_start' => 'events_date_end_date_start_check',
        'date_end>date_start OR is_single_day=true' => 'events_date_end_date_start_or_is_single_day_true_check',
        '(age < 18) OR (email IS NOT NULL)' => 'events_age_18_or_email_is_not_null_check',
    ])->each(function ($expected, $expression) {
        $blueprint = new Blueprint('events');
        $blueprint->check($expression);
        $commands = $blueprint->getCommands();
        $this->assertSame($expected, $commands[0]->constraint);
    });
});

it('can use custom names', function () {
    $blueprint = new Blueprint('events');
    $blueprint->check('MIN(age) >= 18', 'adults_only');
    $commands = $blueprint->getCommands();
    $this->assertSame('adults_only', $commands[0]->constraint);
});

final class StatusInt extends Enum {
    const Draft = 1;
    const Published = 2;
    const Archived = 3;
}

it('can generate enum (consists with integer values) checks', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnum('status', StatusInt::class);
    $commands = $blueprint->getCommands();
    $this->assertSame('status IN (1, 2, 3)', $commands[0]->expression);
});

it('can generate nullable enum (consists with integer values) checks', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnumNullable('status', StatusInt::class);
    $commands = $blueprint->getCommands();
    $this->assertSame('status IS NULL OR status IN (1, 2, 3)', $commands[0]->expression);
});

final class StatusString extends Enum {
    const Draft = 'draft';
    const Published = 'published';
    const Archived = 'archived';
}

it('can generate enum (consists with string values) checks', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnum('status', StatusString::class);
    $commands = $blueprint->getCommands();
    $this->assertSame("status IN ('draft', 'published', 'archived')", $commands[0]->expression);
});

it('can generate nullable enum (consists with string values) checks', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnumNullable('status', StatusString::class);
    $commands = $blueprint->getCommands();
    $this->assertSame("status IS NULL OR status IN ('draft', 'published', 'archived')", $commands[0]->expression);
});

final class StatusIntMixed extends Enum {
    const Draft = 1;
    const Published = 'published';
    const Archived = 'archived';
}

it('throws exception for mixed type enum (int)', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnum('status', StatusIntMixed::class);
})->throws(InvalidArgumentException::class);

final class StatusStringMixed extends Enum {
    const Draft = 'draft';
    const Published = 1;
    const Archived = 'archived';
}

it('throws exception for mixed type enum (string)', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnum('status', StatusStringMixed::class);
})->throws(InvalidArgumentException::class);


final class StatusFloat extends Enum {
    const Draft = 1.0;
    const Published = 2.0;
    const Archived = 3.0;
}
it('throws exception for enum contains other than string or integer', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnum('status', StatusFloat::class);
})->throws(InvalidArgumentException::class);

it('throws exception for enum not exists', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnum('status', "hoobar");
})->throws(InvalidArgumentException::class);

it('throws exception for passing not subclass of Enum', function () {
    $blueprint = new Blueprint('events');
    $blueprint->checkEnum('status', InvalidArgumentException::class);
})->throws(InvalidArgumentException::class);
