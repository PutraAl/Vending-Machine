<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Vending Machine System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <section class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl shadow-black/30 sm:p-10">
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-400">Vending Machine System</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight">Welcome back</h1>
                <p class="mt-2 text-sm text-slate-400">Sign in to manage your vending operations.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-200">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="block w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-600 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20"
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-200">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="block w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-600 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20"
                        placeholder="Enter your password"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 text-sm text-slate-400">
                    <input
                        name="remember"
                        type="checkbox"
                        value="1"
                        @checked(old('remember'))
                        class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-cyan-400 focus:ring-cyan-400"
                    >
                    Remember me
                </label>

                <button type="submit" class="w-full rounded-lg bg-cyan-400 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-300 focus:ring-offset-2 focus:ring-offset-slate-900">
                    Sign in
                </button>
            </form>
        </section>
    </main>
</body>
</html>
