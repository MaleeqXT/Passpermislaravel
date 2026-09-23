<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-dark">
<head>
    @include('common-head')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    @routes
    @vite(['resources/espace-monitor/index.ts'])
    @inertiaHead
</head>

<body class="font-sans antialiased h-full ">
    <div class="bg-rainbow fixed z-0 inset-0 opacity-30"></div>
    @inertia
</body>

</html>