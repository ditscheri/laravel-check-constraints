<?php

namespace Ditscheri\CheckConstraints;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CheckConstraintsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-check-constraints')
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

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
            return (string) Str::of("{$this->prefix}{$this->table}_{$expression}_check")
                ->replaceMatches('#[\W_]+#', '_')
                ->trim('_')
                ->lower();
        });

        Grammar::macro('compileCheck', function (Blueprint $blueprint, Fluent $command) {
            /** @var Grammar $this */
            if($this instanceof SQLiteGrammar) {
                return $this->handleInvalidCheckConstraintDriver();
            }

            return sprintf(
                'alter table %s add constraint %s check (%s)',
                $this->wrapTable($blueprint),
                $this->wrap($command->constraint),
                $command->expression,
            );
        });

        Grammar::macro('compileDropCheck', function (Blueprint $blueprint, Fluent $command) {
            /** @var Grammar $this */
            if($this instanceof SQLiteGrammar) {
                return $this->handleInvalidCheckConstraintDriver();
            }

            $constraints = $this->prefixArray('drop constraint', $this->wrapArray($command->constraints));

            return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $constraints);
        });
        Grammar::macro('handleInvalidCheckConstraintDriver', function () {
            /** @var Grammar $this */
            if(config('check-constraints.sqlite.throw', true)) {
                throw new \RuntimeException('SQLite driver does not support check constraints.');
            }

            return null;
        });

        // SQLiteGrammar::macro('compileCheck', function (Blueprint $blueprint, Fluent $command) {
        //     /** @var SQLiteGrammar $this */
        //     throw new \RuntimeException('SQLite driver does not support check constraints.');
        // });

        // SQLiteGrammar::macro('compileDropCheck', function (Blueprint $blueprint, Fluent $command) {
        //     /** @var SQLiteGrammar $this */
        //     throw new \RuntimeException('SQLite driver does not support check constraints.');
        // });


    }
}
