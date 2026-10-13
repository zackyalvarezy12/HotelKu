@props(['title', 'description', 'eyebrow' => 'AKUN HOTELKU'])

<div class="mb-7">
    <p class="auth-eyebrow">{{ $eyebrow }}</p>
    <h1 class="mt-2 text-2xl font-bold leading-tight text-gray-950 sm:text-3xl">{{ $title }}</h1>
    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $description }}</p>
</div>