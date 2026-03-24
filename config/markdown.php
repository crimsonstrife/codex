<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Code highlighting
    |--------------------------------------------------------------------------
    */
    'code_highlighting' => [
        'enabled' => false,
        'theme'   => 'github-light',
    ],

    /*
    |--------------------------------------------------------------------------
    | Heading anchors
    |--------------------------------------------------------------------------
    | When enabled, spatie/laravel-markdown adds id="" attributes to h1–h6 tags
    | so the Table of Contents JS can link to them.
    */
    'add_anchors_to_headings' => true,

    /*
    |--------------------------------------------------------------------------
    | CommonMark options
    |--------------------------------------------------------------------------
    */
    'html_input'        => 'strip',
    'allow_unsafe_links' => false,
    'max_nesting_level'  => PHP_INT_MAX,

    /*
    |--------------------------------------------------------------------------
    | Extensions
    |--------------------------------------------------------------------------
    | Each entry must implement League\CommonMark\Extension\ExtensionInterface.
    */
    'extensions' => [
        \App\CommonMark\CalloutExtension::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Renderer options
    |--------------------------------------------------------------------------
    */
    'renderer' => [
        'block_separator' => "\n",
        'inner_separator' => "\n",
        'soft_break'      => "\n",
    ],

];
