<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body>
    <div id="app" data-last-auth-email="{{ session('auth.last_email', '') }}"></div>
    </body>
</html>
