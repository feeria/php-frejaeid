<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Freja eID Configuration
    |--------------------------------------------------------------------------
    |
    | This config contains settings for Freja eID API.
    |
    | Production:
    |
    |    Is API in production mode. Can be: true, false
    |
    | Secret Password:
    |
    |    The secret password provided to you by Freja AB.
    |
    | Auth Level:
    |
    |    Authorization level of user in Freja eID application. Can be: BASIC, EXTENDED, PLUS
    |
    */

    'frejaeid' => [
        'production_mode' => env('FREJAEID_API_PRODUCTION_MODE', false),
        'secret_password' => env('FREJAEID_API_PASSWORD', null),
        'auth_level'      => env('FREJAEID_API_AUTH_LEVEL', 'BASIC'),
    ],

];
