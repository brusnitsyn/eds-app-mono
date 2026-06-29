<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <script>
            window.reverb = {
                key: "{{ config('reverb.apps.apps.0.key') }}",
                host: "{{ config('reverb.apps.apps.0.options.host') }}",
                port: "{{ config('reverb.apps.apps.0.options.port') }}",
                scheme: "{{ config('reverb.apps.apps.0.options.scheme') }}",
            }
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
