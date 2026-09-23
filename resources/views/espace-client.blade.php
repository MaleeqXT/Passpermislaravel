<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
<head>
    @include('common-head')
    <script src="https://js.stripe.com/v3/"></script>

    {{-- <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}"> --}}
    @routes
    @vite(['resources/espace-client/index.ts'])
    @inertiaHead
</head>

<body class="font-sans antialiased h-full bg-gray-100">
    @inertia
</body>

</html>
