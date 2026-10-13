<x-guest-layout>
    <x-auth-heading
        title="Selamat datang kembali"
        description="Masuk untuk melanjutkan perjalanan dan melihat pemesanan Anda."
    />

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="auth-label">Alamat email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="auth-label">Kata sandi</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-emerald-800 focus:ring-emerald-700" name="remember">
                <span>Ingat saya</span>
            </label>
            @if (Route::has('password.request'))
                <a class="auth-link text-sm" href="{{ route('password.request') }}">Lupa kata sandi?</a>
            @endif
        </div>

        <button type="submit" class="auth-button w-full">Masuk ke akun</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Belum punya akun? <a href="{{ route('register') }}" class="auth-link">Buat akun</a>
    </p>
</x-guest-layout>
