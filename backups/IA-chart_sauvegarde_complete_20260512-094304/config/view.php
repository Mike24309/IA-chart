<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => (static function (): string {
        $compiledPath = env('VIEW_COMPILED_PATH');

        if (is_string($compiledPath) && $compiledPath !== '') {
            if (preg_match('/^[A-Za-z]:\\\\/', $compiledPath) === 1 || str_starts_with($compiledPath, DIRECTORY_SEPARATOR)) {
                return $compiledPath;
            }

            return base_path($compiledPath);
        }

        return realpath(storage_path('framework/views')) ?: storage_path('framework/views');
    })(),

];
