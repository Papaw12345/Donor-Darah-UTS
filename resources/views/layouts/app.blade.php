<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#9f1d2b">
    <title>@yield('title', 'Sistem Informasi UDD')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="@yield('body-class')">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    @include('partials.navigation')

    <main id="main-content" class="@yield('main-class', 'site-main')">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>Sistem Informasi Manajemen Donor dan Persediaan Darah</p>
            <p>Satu Unit Donor Darah (UDD)</p>
        </div>
    </footer>
</body>
</html>
