<?php

use Neomerx\Limoncello\Settings\Settings as S;

return [

    /*
    |--------------------------------------------------------------------------
    | A list of schemas
    |--------------------------------------------------------------------------
    |
    | Here you can specify what schemas should be used for object on encoding
    | to JSON API format.
    |
    */
    S::SCHEMAS => [
        //ModelXSchema::class,
        //ModelYSchema::class,
        //ModelZSchema::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON encoding options
    |--------------------------------------------------------------------------
    |
    | Here you can specify options to be used while converting data to actual
    | JSON representation with json_encode function.
    |
    | For example if options are set to JSON_PRETTY_PRINT then returned data
    | will be nicely formatted with spaces.
    |
    | see http://php.net/manual/en/function.json-encode.php
    |
    | If this section is omitted default values will be used.
    |
    */
    S::JSON => [
        S::JSON_OPTIONS         => JSON_PRETTY_PRINT,
        S::JSON_DEPTH           => S::JSON_DEPTH_DEFAULT,
        S::JSON_IS_SHOW_VERSION => S::JSON_IS_SHOW_VERSION_DEFAULT,
        S::JSON_VERSION_META    => null,
        S::JSON_URL_PREFIX      => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth settings
    |--------------------------------------------------------------------------
    |
    | Here you can specify options for authentication.
    |
    | Value for AUTH_USER must point to a correct Eloquent model of
    | User class (for example App\User::class).
    |
    */
    S::AUTH => [
        S::AUTH_CODEC => null, // App\TokenCodec::class
    ],

];
