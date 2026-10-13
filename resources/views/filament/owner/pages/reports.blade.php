<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Ekspor booking</h2>
            <p class="mt-1 text-sm text-gray-500">Laporan hanya memuat hotel yang Anda kelola.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('owner.reports.excel') }}"
               class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                <x-heroicon-o-table-cells class="h-5 w-5" />
                Unduh Excel
            </a>
            <a href="{{ route('owner.reports.pdf') }}"
               class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                <x-heroicon-o-document-arrow-down class="h-5 w-5" />
                Unduh PDF
            </a>
        </div>
    </div>
</x-filament-panels::page>