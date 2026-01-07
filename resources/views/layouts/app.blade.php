<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Course de Noël' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
