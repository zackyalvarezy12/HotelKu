<x-guest-layout>
    <x-auth-heading
        title="Verifikasi alamat email"
        description="Satu langkah lagi. Buka tautan verifikasi yang kami kirim untuk mengaktifkan akun Anda."
    />

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status status="Tautan verifikasi baru sudah dikirim ke alamat email Anda." />
    @endif

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="auth-button w-full">Kirim ulang tautan verifikasi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-link w-full rounded-md py-2 text-sm">
                Keluar dari akun
            </button>
        </form>
    </div>
</x-guest-layout>
