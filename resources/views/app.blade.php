<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <meta
        name="description"
        content="LinkBox — сервис для создания персональной страницы со всеми вашими ссылками. Группируйте ссылки по блокам, добавляйте обложки и меняйте фон — без кода."
    >

    <meta
        name="keywords"
        content="linkbox, ссылки в одном месте, страница со ссылками, link in bio, биолинк, агрегатор ссылок, персональная страница, ссылки для соцсетей"
    >

    <meta property="og:type" content="website">
    <meta property="og:title" content="LinkBox — все ваши ссылки на одной странице">
    <meta property="og:description" content="Соберите персональную страницу со ссылками: группы, обложки, свой фон. Просто и без кода.">
    <meta property="og:image" content="{{ asset('apple-touch-icon.png') }}">

    <title>LinkBox — все ваши ссылки на одной странице</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body>

<div id="app">
    <link-blocks></link-blocks>
</div>

</body>
</html>
