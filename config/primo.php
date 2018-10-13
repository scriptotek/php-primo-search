<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration for hosted Primo
    |--------------------------------------------------------------------------
    */

    'apiKey' => env('PRIMO_API_KEY'),
    'region' => env('PRIMO_REGION'),   // 'eu', 'na' or 'ap'

    /*
    |--------------------------------------------------------------------------
    | Configuration for on-premises Primo
    |--------------------------------------------------------------------------
    */

    'baseUrl' => env('PRIMO_BASE_URL'),  // <base-local-url>/primo_library/libweb/webservices/rest/v1/
    'searchUrl' => env('PRIMO_SEARCh_URL'),  // Optional, if not located in the default location under {baseUrl}
    'inst' => env('PRIMO_INST'),  // Institution ID

    /*
    |--------------------------------------------------------------------------
    | General configuration
    |--------------------------------------------------------------------------
    */
    'vid'    => env('PRIMO_VID'),   // Default view ID
    'scope'  => env('PRIMO_SCOPE', 'default_scope'),  // Default scope

];
