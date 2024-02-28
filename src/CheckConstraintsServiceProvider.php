<?php

namespace Ditscheri\CheckConstraints;

use BenSampo\Enum\Enum;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use InvalidArgumentException;
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

        Blueprint::macro('checkEnum', function(string $column, string $enumClass, ?string $constraint = null) {
            /** @var Blueprint $this */
            $constraint = $constraint ?: $this->createCheckName($column);
            $expression = $this->createEnumExpression($column, $enumClass, false);

            return $this->check($expression, $constraint);
        });

        Blueprint::macro('checkEnumNullable', function(string $column, string $enumClass, ?string $constraint = null) {
            /** @var Blueprint $this */
            $constraint = $constraint ?: $this->createCheckName($column);
            $expression = $this->createEnumExpression($column, $enumClass, true);

            return $this->check($expression, $constraint);
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

        Blueprint::macro('createEnumExpression', function (string $column, string $enumClass, bool $nullable = false) {
            /** @var Blueprint $this */
            /** @var class-string $enumClass */
            if (!class_exists($enumClass) || !is_subclass_of($enumClass, Enum::class)) {
                throw new InvalidArgumentException("$enumClass must be existing and subclass of " . Enum::class);
            }

            $values = collect($enumClass::getValues());
            $expression = "{$column} IN ";
            if(is_int($values->first())){
                if($values->some(fn($value) => !is_int($value))){
                    throw new InvalidArgumentException("All values in $enumClass must be same type (string or integer).");
                }
                $expression .= "(".implode(", ", $values->toArray()).")";
            }else if(is_string($values->first())){
                if($values->some(fn($value) => !is_string($value))){
                    throw new InvalidArgumentException("All values in $enumClass must be same type (string or integer)");
                }
                $expression .= "('".implode("', '", $values->toArray())."')";
            }else{
                throw new InvalidArgumentException("Only string and integer values are supported");
            }

            if($nullable){
                $expression = "{$column} IS NULL OR {$expression}";
            }

            return $expression;
        });

        Grammar::macro('compileCheck', function (Blueprint $blueprint, Fluent $command) {
            /** @var Grammar $this */
            if ($this instanceof SQLiteGrammar) {
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
            if ($this instanceof SQLiteGrammar) {
                return $this->handleInvalidCheckConstraintDriver();
            }

            $constraints = $this->prefixArray('drop constraint', $this->wrapArray($command->constraints));

            return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $constraints);
        });
        Grammar::macro('handleInvalidCheckConstraintDriver', function () {
            /** @var Grammar $this */
            if (config('check-constraints.sqlite.throw', true)) {
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
