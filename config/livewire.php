<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Livewire Configuration
    |--------------------------------------------------------------------------
    |
    | This is where you may specify livewire's default "layout" that'll be used
    | to wrap all of your components. It can be updated on a per-component
    | basis using @livewire('your-component', ['layout' => 'app'])
    |
    */

    'layout' => 'components.layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Auto-inject Livewire's Assets
    |--------------------------------------------------------------------------
    */

    'inject_assets' => true,

    /*
    |--------------------------------------------------------------------------
    | Script Nonce
    |--------------------------------------------------------------------------
    */

    'script_nonce' => null,

    /*
    |--------------------------------------------------------------------------
    | Livewire Request Payload Max Size
    |--------------------------------------------------------------------------
    |
    | The maximum size (in KB) of a Livewire request payload. Increase this if
    | you're working with large amounts of data in your Livewire components.
    |
    */

    'payload' => [
        'max_size' => 5242880, // 5MB in bytes
    ],

];
