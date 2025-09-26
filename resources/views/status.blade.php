<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Muslim Finder Backend - System Status</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/status.jsx'])
    
    <!-- App Data -->
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
            appName: '{{ config("app.name") }}',
            appVersion: '1.0.0',
            appEnvironment: '{{ app()->environment() }}',
            appDebug: '{{ config("app.debug") }}',
            appUrl: '{{ config("app.url") }}',
        };
    </script>
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900">
    <div id="status-root"></div>
</body>
</html>
