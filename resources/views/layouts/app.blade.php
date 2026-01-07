<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'Petshop')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body>

@include('partials.navbar')

@yield('content')

@include('partials.footer')

</body>
</html>
