<x-guest-layout>
    <x-auth-heading
        title="Konfirmasi identitas"
        description="Demi keamanan, masukkan kembali kata sandi Anda untuk melanjutkan."
    />

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="auth-label">Kata sandi</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" class="auth-button w-full">Konfirmasi dan lanjutkan</button>
    </form>
</x-guest-layout>
