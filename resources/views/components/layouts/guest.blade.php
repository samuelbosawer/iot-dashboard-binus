@props(['title' => 'Login', 'description' => null, 'noindex' => false])

<!DOCTYPE html>
<html lang="id" class="h-full motion-safe:scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <x-seo :title="$title" :description="$description" :noindex="$noindex" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans antialiased">
    {{ $slot }}

    @stack('scripts')
</body>
</html>
