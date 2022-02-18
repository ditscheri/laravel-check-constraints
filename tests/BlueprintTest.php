<?php

use Illuminate\Database\Schema\Blueprint;

it('can generate default names', function () {
    collect([
        'date_end>=date_start' => 'events_date_end_date_start_check',
        'date_end>date_start OR is_single_day=true' => 'events_date_end_date_start_or_is_single_day_true_check',
        '(age < 18) OR (email IS NOT NULL)' => 'events_age_18_or_email_is_not_null_check'
    ])->each(function($expected, $expression) {
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
