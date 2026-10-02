<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Admin') | {{ config('app.name', 'ProjectHub') }}
    </title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap"
        rel="stylesheet"
    >

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @livewireStyles
</head>

<body class="font-sans antialiased text-slate-800">

    <div class="min-h-screen bg-[#f7f5f2]">

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 hidden w-64 border-r
                   border-slate-200 bg-[#fffdfa]
                   lg:flex lg:flex-col"
        >

            {{-- Brand --}}
            <div class="flex items-center h-20 px-6 border-b border-slate-200">

                <a
                    href="{{ route('admin.dashboard') }}"
                    wire:navigate
                    class="flex items-center gap-3"
                >
                    <x-application-logo class="w-10 h-10" />

                    <div>
                        <div class="text-lg font-bold tracking-tight text-slate-900">
                            PROJECT<span class="text-red-600">HUB</span>
                        </div>

                        <div class="text-xs text-slate-400">
                            Project Management
                        </div>
                    </div>
                </a>

            </div>


            {{-- Navigation --}}
            <nav class="flex-1 px-4 py-6 space-y-2">

                {{-- Dashboard --}}
                <a
                    href="{{ route('admin.dashboard') }}"
                    wire:navigate
                    class="flex items-center gap-3 rounded-xl
                           px-4 py-3 text-sm transition
                        {{ request()->routeIs('admin.dashboard')
                            ? 'bg-red-50 font-semibold text-red-600'
                            : 'font-medium text-slate-600 hover:bg-red-50 hover:text-red-600'
                        }}"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-12h8V3h-8v6Z"
                        />
                    </svg>

                    Dashboard
                </a>


                <a
                    href="{{ route('admin.users.index') }}"
                    wire:navigate
                    class="flex items-center gap-3 rounded-xl
                        px-4 py-3 text-sm transition
                        {{ request()->routeIs('admin.users.*')
                            ? 'bg-red-50 font-semibold text-red-600'
                            : 'font-medium text-slate-600 hover:bg-red-50 hover:text-red-600'
                        }}"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m-1-11.13a4 4 0 0 1 0 7.75"
                        />
                    </svg>

                    User Management
                </a>

            </nav>


            {{-- Logged-in User --}}
            <div class="p-4 border-t border-slate-200">

                <div class="flex items-center gap-3 px-2 mb-3">

                    <div
                        class="flex items-center justify-center w-10 h-10 font-bold text-red-600 bg-red-100 rounded-full"
                    >
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div class="flex-1 min-w-0">

                        <p class="text-sm font-semibold truncate text-slate-800">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="text-xs text-slate-400">
                            Administrator
                        </p>

                    </div>

                </div>


                <a
                    href="{{ route('profile') }}"
                    wire:navigate
                    class="block px-3 py-2 mb-1 text-sm transition rounded-lg text-slate-600 hover:bg-slate-100"
                >
                    Profile
                </a>


                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="w-full px-3 py-2 text-sm text-left text-red-600 transition rounded-lg hover:bg-red-50"
                    >
                        Keluar
                    </button>
                </form>

            </div>

        </aside>


        {{-- Main --}}
        <div class="lg:pl-64">

            {{-- Header --}}
            <header
                class="sticky top-0 z-20 border-b
                       border-slate-200/80
                       bg-[#f7f5f2]/90 backdrop-blur"
            >

                <div
                    class="flex items-center justify-between h-20 px-6 lg:px-8"
                >

                    <div>

                        <p
                            class="text-xs font-semibold tracking-wider text-red-600 uppercase"
                        >
                            Administrator
                        </p>

                        <h1 class="text-xl font-bold text-slate-900">
                            @yield('header', 'ProjectHub')
                        </h1>

                    </div>


                    <div class="text-sm text-slate-500">
                        {{ auth()->user()->name }}
                    </div>

                </div>

            </header>


            {{-- Page Content --}}
            <main class="p-6 lg:p-8">
                @yield('content')
            </main>

        </div>

    </div>


    @livewireScripts
</body>
</html>
