<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard') · VendingOS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html {
            background: #060a12;
        }

        body {
            background:
                radial-gradient(circle at 82% 8%,
                    rgba(34, 211, 238, 0.06),
                    transparent 28%),
                radial-gradient(circle at 18% 75%,
                    rgba(59, 130, 246, 0.04),
                    transparent 25%),
                #060a12;
        }

        .glass {
            background: rgba(7, 11, 20, 0.88);
            backdrop-filter: blur(18px);
        }

        .panel {
            border: 1px solid rgba(148, 163, 184, 0.08);
            background: rgba(13, 20, 34, 0.82);
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.02);
        }

        .panel-hover {
            transition:
                border-color 180ms ease,
                transform 180ms ease,
                background 180ms ease;
        }

        .panel-hover:hover {
            transform: translateY(-2px);
            border-color: rgba(148, 163, 184, 0.14);
            background: rgba(15, 23, 42, 0.92);
        }

        .nav-item {
            transition:
                background 160ms ease,
                color 160ms ease,
                border-color 160ms ease;
        }

        .nav-item:hover {
            color: white;
            background: rgba(255, 255, 255, 0.035);
        }

        .nav-active {
            color: rgb(103 232 249);
            background: rgba(34, 211, 238, 0.08);
            border: 1px solid rgba(34, 211, 238, 0.10);
        }

        .grid-bg {
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.025) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .status-pulse {
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: .45;
                transform: scale(.82);
            }
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden text-slate-100 antialiased">

    {{-- Mobile overlay --}}
    <div id="sidebar-overlay"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-[2px] transition-opacity duration-200 lg:hidden"></div>

    <div class="min-h-screen lg:flex">

        @include('partials.sidebar')

        <div class="min-w-0 flex-1">

            @include('partials.navbar')

            <main class="grid-bg min-h-[calc(100vh-73px)] px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-[1600px]">
                    @yield('content')
                </div>
            </main>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const openButton = document.getElementById('open-mobile-sidebar');
            const closeButton = document.getElementById('close-mobile-sidebar');

            if (!sidebar || !overlay || !openButton || !closeButton) {
                return;
            }

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');

                overlay.classList.remove('opacity-0', 'pointer-events-none');
                overlay.classList.add('opacity-100', 'pointer-events-auto');

                document.body.classList.add('overflow-hidden');
            }

            function closeSidebar() {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');

                overlay.classList.remove('opacity-100', 'pointer-events-auto');
                overlay.classList.add('opacity-0', 'pointer-events-none');

                document.body.classList.remove('overflow-hidden');
            }

            openButton.addEventListener('click', openSidebar);
            closeButton.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 1024) {
                        closeSidebar();
                    }
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && window.innerWidth < 1024) {
                    closeSidebar();
                }
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1024) {
                    closeSidebar();
                }
            });

            // IMPORTANT: pastikan kondisi awal benar-benar tertutup
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');

            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.classList.remove('opacity-100', 'pointer-events-auto');

            document.body.classList.remove('overflow-hidden');
        });
    </script>

</body>

</html>