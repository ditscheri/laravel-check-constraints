<?php

namespace Ditscheri\CheckConstraints\Commands;

use Illuminate\Console\Command;

class CheckConstraintsCommand extends Command
{
    public $signature = 'laravel-check-constraints';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
