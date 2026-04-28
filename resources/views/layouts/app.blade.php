<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Certificate System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    @yield('content')
    @stack('scripts')
    <script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
        document.querySelectorAll('[data-dismiss-target]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById(button.dataset.dismissTarget)?.remove();
            });
        });
    </script>
</body>
</html>
