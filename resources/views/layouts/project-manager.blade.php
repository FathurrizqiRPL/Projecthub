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
        @yield('title', 'Project Manager') |
        {{ config('app.name', 'ProjectHub') }}
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
            class="fixed inset-y-0 left-0 hidden w-64
                   border-r border-slate-200 bg-[#fffdfa]
                   lg:flex lg:flex-col"
        >

            {{-- Brand --}}
            <div class="flex h-20 items-center border-b border-slate-200 px-6">

                <a
                    href="{{ route('project-manager.dashboard') }}"
                    wire:navigate
                    class="flex items-center gap-3"
                >
                    <x-application-logo class="h-10 w-10" />

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
            <nav class="flex-1 space-y-2 px-4 py-6">

                {{-- Dashboard --}}
                <a
                    href="{{ route('project-manager.dashboard') }}"
                    wire:navigate
                    class="flex items-center gap-3 rounded-xl
                           px-4 py-3 text-sm transition
                        {{ request()->routeIs('project-manager.dashboard')
                            ? 'bg-red-50 font-semibold text-red-600'
                            : 'font-medium text-slate-600 hover:bg-red-50 hover:text-red-600'
                        }}"
                >
                    <svg
                        class="h-5 w-5"
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


                {{-- Projects --}}
                <a
                    href="{{ route('project-manager.projects.index') }}"
                    wire:navigate
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition
                        {{ request()->routeIs('project-manager.projects.*')
                            ? 'bg-red-50 font-semibold text-red-600'
                            : 'font-medium text-slate-600 hover:bg-red-50 hover:text-red-600'
                        }}"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M3 7h5l2 2h11v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"
                        />
                    </svg>

                    Projects
                </a>


                {{-- Tasks --}}
                <div
                    class="flex cursor-not-allowed items-center gap-3
                           rounded-xl px-4 py-3 text-sm
                           font-medium text-slate-400"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"
                        />
                    </svg>

                    Tasks

                    <span
                        class="ml-auto rounded-full bg-slate-100
                               px-2 py-0.5 text-[10px] font-semibold
                               text-slate-400"
                    >
                        Soon
                    </span>
                </div>

            </nav>


            {{-- User --}}
            <div class="border-t border-slate-200 p-4">

                <div class="mb-3 flex items-center gap-3 px-2">

                    <div
                        class="flex h-10 w-10 items-center justify-center
                               rounded-full bg-red-100
                               font-bold text-red-600"
                    >
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div class="min-w-0 flex-1">

                        <p class="truncate text-sm font-semibold text-slate-800">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="text-xs text-slate-400">
                            Project Manager
                        </p>

                    </div>

                </div>


                <a
                    href="{{ route('profile') }}"
                    wire:navigate
                    class="mb-1 block rounded-lg px-3 py-2
                           text-sm text-slate-600 transition
                           hover:bg-slate-100"
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
                        class="w-full rounded-lg px-3 py-2
                               text-left text-sm text-red-600
                               transition hover:bg-red-50"
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
                    class="flex h-20 items-center
                           justify-between px-6 lg:px-8"
                >

                    <div>

                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wider text-red-600"
                        >
                            Project Manager
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


            {{-- Content --}}
            <main class="p-6 lg:p-8">
                @yield('content')
            </main>

        </div>

    </div>

    @livewireScripts
</body>
</html>
