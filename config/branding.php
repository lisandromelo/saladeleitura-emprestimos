<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identidade visual (compartilhável / personalizável)
    |--------------------------------------------------------------------------
    |
    | Nome, logo e cores podem ser alterados por ambiente (.env) sem mudar código.
    | Outras instituições: troque BRAND_* e, se quiser, substitua public/images/logo.png.
    |
    */

    'name' => env('BRAND_NAME', env('APP_NAME', 'Sala de Leitura CEEBN')),

    'tagline' => env('BRAND_TAGLINE', 'Área do Livro Espírita'),

    'logo' => env('BRAND_LOGO', '/images/logo.png'),

    'colors' => [
        'primary' => env('BRAND_PRIMARY', '#006D5B'),
        'primary_dark' => env('BRAND_PRIMARY_DARK', '#005546'),
        'secondary' => env('BRAND_SECONDARY', '#929497'),
        'paper' => env('BRAND_PAPER', '#F5FAF8'),
        'ink' => env('BRAND_INK', '#1F2933'),
        'ok' => env('BRAND_OK', '#2F6B4F'),
        'warn' => env('BRAND_WARN', '#9A3412'),
    ],

];
