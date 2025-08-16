@php
    $generaleSetting = App\Models\GeneraleSetting::first();

    $title = $generaleSetting?->title ?? config('app.name', 'ReadyEcommerce');
    $favicon = $generaleSetting?->favicon ?? asset('assets/favicon.png');
    
    // Get current locale from session, cookie, or default to 'en'
    $currentLocale = session('locale') ?? request()->cookie('locale') ?? app()->getLocale() ?? 'en';
    
    // Set body classes and direction based on locale
    $bodyClasses = 'lang-' . $currentLocale;
    $direction = $currentLocale === 'ar' ? 'rtl' : 'ltr';
    
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" dir="{{ $direction }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('/') }}">
    <meta name="app-url" content="{{ url('/') }}">
    <!-- description -->
    <meta name="description" content="ecommerce website">

    <title>{{ $title }}</title>
    <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">

    <!-- Google Fonts for Arabic Support -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@100..900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="{{ $bodyClasses }}">
    <div id="app"></div>

    @vite('resources/js/app.js')
</body>

</html>
