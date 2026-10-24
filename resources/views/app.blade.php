<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="demo-enabled" content="{{ app()->environment('local', 'testing') ? '1' : '0' }}">
    <meta name="viewer-authenticated" content="{{ auth()->check() ? '1' : '0' }}">
    <meta name="theme-color" content="#fc7445">
    <meta name="description" content="Связь — знакомства на вашей волне. Анкеты, общие интересы и разговоры, с которых начинается что-то настоящее. Для людей старше 18 лет.">
    <title>{{ request()->is('cabinet') ? 'Личный кабинет — Связь' : 'Связь — хочется кого-то своего' }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body><div id="root"></div></body>
</html>
