<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('common-head')
    @routes
    @vite(['resources/espace-secretary/index.ts'])
    @inertiaHead
</head>
<body class="font-sans antialiased h-full bg-dark">
    @inertia
</body>
</html>
