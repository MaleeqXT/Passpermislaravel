<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
<head>
    @include('common-head')
    @routes
    @vite(['resources/espace-admin/index.ts'])
    @inertiaHead
</head>

<body class="font-sans antialiased h-full bg-dark">
    <div class="bg-rainbow fixed z-0 inset-0 opacity-30"></div>
    @inertia
</body>

</html>