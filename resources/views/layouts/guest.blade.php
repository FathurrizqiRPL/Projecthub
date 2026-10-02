<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ProjectHub') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-slate-800">
    <div class="min-h-screen bg-[#f7f5f2]">
        <div class="min-h-screen lg:grid lg:grid-cols-2">

            {{-- Branding Side --}}
            <div class="relative hidden p-12 overflow-hidden lg:flex lg:flex-col lg:justify-between xl:p-16">
                <div class="absolute rounded-full -left-32 -top-32 h-96 w-96 bg-red-100/70 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 rounded-full h-72 w-72 bg-red-50 blur-3xl"></div>

                <div class="relative z-10">
                    <a href="/" wire:navigate class="inline-flex items-center gap-3">
                        <x-application-logo class="h-11 w-11" />

                        <div>
                            <div class="text-xl font-bold tracking-tight text-slate-900">
                                PROJECT<span class="text-red-600">HUB</span>
                            </div>
                            <div class="text-xs text-slate-500">
                                Project Management System
                            </div>
                        </div>
                    </a>
                </div>

                <div class="relative z-10 max-w-lg">
                    <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-red-600">
                        Work smarter
                    </p>

                    <h1 class="text-4xl font-bold leading-tight text-slate-900 xl:text-5xl">
                        Kelola proyek, tim, dan tugas dengan lebih terstruktur.
                    </h1>

                    <p class="max-w-md mt-6 text-base leading-7 text-slate-600">
                        Pantau perkembangan proyek, pembagian tugas, verifikasi pekerjaan,
                        hingga maintenance dalam satu sistem.
                    </p>
                </div>

                <div class="relative z-10 text-sm text-slate-400">
                    ProjectHub • Internal Project Management
                </div>
            </div>

            {{-- Form Side --}}
            <div class="flex items-center justify-center min-h-screen px-5 py-10 sm:px-8">
                <div class="w-full max-w-md">

                    {{-- Mobile Brand --}}
                    <div class="flex justify-center mb-8 lg:hidden">
                        <a href="/" wire:navigate class="flex items-center gap-3">
                            <x-application-logo class="w-10 h-10" />

                            <div>
                                <div class="text-xl font-bold text-slate-900">
                                    PROJECT<span class="text-red-600">HUB</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    Project Management System
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="rounded-3xl border border-slate-200/70 bg-[#fffdfa] p-6 shadow-[0_20px_60px_-25px_rgba(15,23,42,0.18)] sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-xs text-center text-slate-400">
                        Sistem internal perusahaan
                    </p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
