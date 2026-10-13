@props(['status'])

@if ($status)
    <div role="status" {{ $attributes->merge(['class' => 'mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800']) }}>
        {{ $status }}
    </div>
@endif
