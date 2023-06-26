<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'bri' => [
        'host' => env('BRI_HOST','https://sandbox.partner.api.bri.co.id'),
        'key' => env('BRI_KEY_PRIVATE_NAME'),
    ],
    'mandiri' => [
        'host' => env('MANDIRI_HOST','https://mandiri-snap.linkaja.dev:4101'),
        'key' => env('MANDIRI_KEY_PRIVATE_NAME','mandiri'),
    ],

];
