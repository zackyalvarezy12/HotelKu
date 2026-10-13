<x-guest-layout>
    <x-auth-heading
        title="Atur ulang kata sandi"
        description="Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat kata sandi baru."
    />

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="auth-label">Alamat email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="auth-button w-full">Kirim tautan reset</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Ingat kata sandi? <a href="{{ route('login') }}" class="auth-link">Kembali masuk</a>
    </p>
</x-guest-layout>
