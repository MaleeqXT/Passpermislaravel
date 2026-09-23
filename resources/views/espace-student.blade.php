<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="size-full" >
<head>
    @include('common-head')
    <script src="https://js.stripe.com/v3/"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    @routes
    @vite(['resources/espace-student/index.ts'])
    @inertiaHead
</head>

<body class="font-sans text-md antialiased size-full bg-dark">
    @inertia
</body>

</html>
