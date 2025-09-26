<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Muslim Finder Backend') }} - Welcome</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/welcome.jsx'])
    
    <!-- App Data -->
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
            appName: '{{ config("app.name") }}',
            appUrl: '{{ config("app.url") }}',
            appVersion: '1.0.0'
        };
    </script>
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <div id="welcome-app"></div>
    
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="fixed inset-0 bg-gray-50 dark:bg-gray-900 flex items-center justify-center z-50">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400 text-lg">Loading {{ config('app.name') }}...</p>
        </div>
    </div>
    
    <script>
        // Hide loading spinner when React app loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const spinner = document.getElementById('loading-spinner');
                if (spinner) {
                    spinner.style.display = 'none';
                }
            }, 1500);
        });
    </script>
</body>
</html>
