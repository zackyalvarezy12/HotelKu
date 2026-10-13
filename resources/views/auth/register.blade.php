<x-guest-layout>
    <x-auth-heading
        title="Mulai perjalanan Anda"
        description="Buat akun untuk menyimpan pilihan hotel dan mengelola pemesanan dengan mudah."
    />

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="auth-label">Nama lengkap</label>
            <input id="name" class="auth-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Nama sesuai identitas">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="auth-label">Alamat email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="auth-label">Kata sandi</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" placeholder="Buat kata sandi">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="auth-label">Ulangi kata sandi</label>
            <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Masukkan ulang kata sandi">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="auth-button w-full">Buat akun</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Sudah punya akun? <a href="{{ route('login') }}" class="auth-link">Masuk</a>
    </p>
</x-guest-layout>
