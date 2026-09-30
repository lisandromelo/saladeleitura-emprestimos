<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-name" content="{{ config('branding.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('branding.name') }}</title>
    <link rel="icon" type="image/png" href="{{ config('branding.logo') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="min-h-screen antialiased"
    style="
        --brand-primary: {{ config('branding.colors.primary') }};
        --brand-primary-dark: {{ config('branding.colors.primary_dark') }};
        --brand-secondary: {{ config('branding.colors.secondary') }};
        --brand-paper: {{ config('branding.colors.paper') }};
        --brand-ink: {{ config('branding.colors.ink') }};
        --brand-ok: {{ config('branding.colors.ok') }};
        --brand-warn: {{ config('branding.colors.warn') }};
    ">
    @inertia
</body>
</html>
