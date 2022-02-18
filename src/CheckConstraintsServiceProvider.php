<?php

namespace Ditscheri\CheckConstraints;

use Ditscheri\CheckConstraints\Commands\CheckConstraintsCommand;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CheckConstraintsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-check-constraints')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-check-constraints_table')
            ->hasCommand(CheckConstraintsCommand::class);
    }

    public function packageRegistered()
    {
        Blueprint::macro('check', function (string $expression, ?string $constraint = null) {
            /** @var Blueprint $this */
            $constraint = $constraint ?: $this->createCheckName($expression);

            return $this->addCommand('check', compact('expression', 'constraint'));
        });

        Blueprint::macro('dropCheck', function (string|array $constraints) {
            /** @var Blueprint $this */
            $constraints = is_array($constraints) ? $constraints : func_get_args();

            return $this->addCommand('dropCheck', compact('constraints'));
        });

        Blueprint::macro('createCheckName', function (string $expression) {
            /** @var Blueprint $this */
            return Str::of("{$this->prefix}{$this->table}_{$expression}_check")
                ->replaceMatches('#[\W_]+#', '_')
                ->trim('_')
                ->lower()
                ->value();
        });

        MySqlGrammar::macro('compileCheck', function (Blueprint $blueprint, Fluent $command) {
            /** @var MySqlGrammar $this */
            return sprintf(
                'alter table %s add constraint %s check (%s)',
                $this->wrapTable($blueprint),
                $this->wrap($command->constraint),
                $command->expression,
            );
        });

        MySqlGrammar::macro('compileDropCheck', function (Blueprint $blueprint, Fluent $command) {
            /** @var MySqlGrammar $this */
            $constraints = $this->prefixArray('drop constraint', $this->wrapArray($command->constraints));

            return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $constraints);
        });
    }
}
