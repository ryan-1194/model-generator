<?php

namespace App\Actions;

abstract class Action
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function run(...$arguments): mixed
    {
        if (! method_exists(static::class, 'handle')) {
            throw new \Exception('Unimplemented function [handle] in class ['.static::class.']');
        }

        return static::make()->handle(...$arguments);
    }
}
