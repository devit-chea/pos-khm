<!DOCTYPE html>
<html lang="en">
@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>

    <!-- Load Tailwind CSS -->
    <link href="{{ asset($asset_prefix . 'frontend/css/tailwind.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Barlow', sans-serif;
            background-color: #f9fafb; /* bg-gray-50 */
        }
        .storefront-company-logo {
            display: block;
            width: auto;
            height: auto;
            max-width: 150px;
            max-height: 48px;
            object-fit: contain;
        }
    </style>
</head>
<body class="text-gray-800 antialiased">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ url('/') }}" class="block max-w-[150px] max-h-12 text-2xl font-bold uppercase tracking-widest text-gray-900">
                            @include('ecommerce::frontend.layout.header.storefront-logo')
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ url('/') }}" class="border-black text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-xs uppercase tracking-widest font-medium">Home</a>
                        <a href="{{ url('/shop') }}" class="border-black text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-xs uppercase tracking-widest font-medium">Shop</a>
                        <a href="{{ url('products/collections/all') }}" class="border-black text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-xs uppercase tracking-widest font-medium">Collections</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="{{ url('/cart') }}" class="p-2 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Cart</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 md:flex md:items-center md:justify-between lg:px-8">
            <div class="flex justify-center space-x-6 md:order-2">
                <!-- Social links can go here -->
            </div>
            <div class="mt-8 md:mt-0 md:order-1">
                <p class="text-center text-base text-gray-400">
                    &copy; {{ date('Y') }} {{ $ecommerce_setting->site_title ?? $general_setting->site_title ?? config('app.name') }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
    <script>
        document.addEventListener('change', function (event) {
            if (!event.target.matches('.minimal-cart-form select[name="variant_id"]')) return;
            var quantity = event.target.form.querySelector('input[name="qty"]');
            var option = event.target.options[event.target.selectedIndex];
            if (!quantity) return;
            if (option && option.dataset.max) quantity.max = option.dataset.max;
            else quantity.removeAttribute('max');
        });

        document.addEventListener('submit', function (event) {
            var form = event.target.closest('.minimal-cart-form');
            if (!form) return;

            event.preventDefault();
            var button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) throw new Error(data.message || 'Unable to update cart.');
                    return data;
                });
            }).then(function () {
                window.location.href = form.dataset.successUrl || @json(route('cart'));
            }).catch(function (error) {
                var message = document.getElementById('minimal-cart-message');
                if (message) {
                    message.textContent = error.message;
                    message.classList.remove('hidden');
                }
                if (button) button.disabled = false;
            });
        });
    </script>
</body>
</html>
