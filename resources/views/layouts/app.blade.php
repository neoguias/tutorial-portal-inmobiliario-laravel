<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Pisos y casas en Valencia' }} · {{ config('portal.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
        <nav class="flex h-14 items-center gap-6 px-4">
            <a href="{{ route('search') }}" wire:navigate class="text-xl font-bold text-teal-700">{{ config('portal.name') }}</a>
            <a href="{{ route('search', ['operation' => 'sale']) }}" wire:navigate class="text-sm hover:text-teal-700">Comprar</a>
            <a href="{{ route('search', ['operation' => 'rent']) }}" wire:navigate class="text-sm hover:text-teal-700">Alquilar</a>
        </nav>
    </header>

    {{ $slot }}

    {{-- Livewire y Alpine se cargan desde app.js, así que aquí solo va su configuración. --}}
    @livewireScriptConfig
</body>
</html>
