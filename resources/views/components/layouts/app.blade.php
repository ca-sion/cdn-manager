<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="application-name" content="{{ config('app.name') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>
        <link rel="icon" type="image/svg+xml" href="https://coursedenoel.ch/assets/ssk/logo-cdn.svg">
        <link rel="mask-icon" href="https://coursedenoel.ch/assets/ssk/logo-cdn.svg" color="#5bbad5">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,600" rel="stylesheet" />

        <!-- Styles -->
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>

        @filamentStyles
        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">
        {{ $slot }}

        @livewire('notifications')

        @filamentScripts
        @vite('resources/js/app.js')
    </body>
</html>
