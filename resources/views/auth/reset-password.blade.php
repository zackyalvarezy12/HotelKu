<x-guest-layout>
    <x-auth-heading
        title="Buat kata sandi baru"
        description="Pilih kata sandi yang kuat agar akun dan pemesanan Anda tetap aman."
    />

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="auth-label">Alamat email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="auth-label">Kata sandi baru</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" placeholder="Kata sandi baru">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="auth-label">Ulangi kata sandi baru</label>
            <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Masukkan ulang kata sandi">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="auth-button w-full">Simpan kata sandi baru</button>
    </form>
</x-guest-layout>
