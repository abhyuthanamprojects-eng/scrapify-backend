<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

        <title>Scrapify — कबाड़ हटाओ, कैश पाओ | Doorstep Scrap Pickup</title>
        <meta name="description" content="India's smartest doorstep scrap pickup. Sell scrap, kabadi, raddi, ewaste online — local kawadi wala at your door. Book free pickup, get paid instantly via UPI.">
        <meta property="og:title" content="Scrapify — कबाड़ हटाओ, कैश पाओ">
        <meta property="og:description" content="Your local kawadi wala online — sell scrap, kabadi, raddi, ewaste from home. Doorstep scrap pickup, instant UPI payment.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="https://scrapi5.com">
        <link rel="canonical" href="https://scrapi5.com">
        <meta name="keywords" content="scrap pickup, sell scrap online, kabadi, kawadi, kabadiwala, kawadi wala, local kawadi, local kabadi, scrap kawadi, kabad, raddi, raddiwala, scrap dealer near me, scrap buyer, online kabadi, doorstep scrap pickup, sell old newspaper, sell old electronics, e-waste pickup, ewaste, e waste, sell ewaste online, ewaste recycling, electronic waste, metal scrap, iron scrap, plastic scrap, paper scrap, कबाड़, कबाड़ी, रद्दी, कबाड़ीवाला, स्क्रैप">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        <script>
            window.Laravel = {
                check: @json(auth()->check()),
                user: @json(auth()->user())
            };
        </script>
        @viteReactRefresh
        @vite(['resources/js/Frontend/styles.css', 'resources/js/frontend.tsx'])

    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
