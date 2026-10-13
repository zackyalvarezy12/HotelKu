<?php

namespace App\Livewire\Guest;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Form pencarian hotel: kota, tanggal, jumlah tamu. */
class SearchForm extends Component
{
    #[Url(as: 'kota', except: '')]
    public string $city = '';

    #[Url(as: 'masuk', except: '')]
    public string $checkIn = '';

    #[Url(as: 'keluar', except: '')]
    public string $checkOut = '';

    #[Url(as: 'tamu', except: 2)]
    public int $guests = 2;

    /** Daftar kota yang tersedia untuk dropdown. */
    public array $cities = [];

    /** Tampilan ringkas dipakai pada halaman hasil pencarian. */
    public bool $compact = false;

    public function mount(bool $compact = false): void
    {
        $this->compact = $compact;

        $this->cities = \App\Models\Hotel::query()
            ->publiclyVisible()
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->all();
    }

    /** Kosongkan tanggal bila kota berubah agar tidak ada rentang menggantung. */
    public function updatedCity(): void
    {
        // tidak ada aksi khusus; disediakan untuk hook perubahan filter
    }

    protected function rules(): array
    {
        return [
            'city' => ['nullable', 'string', 'max:100'],
            'checkIn' => ['nullable', 'date', 'after_or_equal:today'],
            'checkOut' => ['nullable', 'required_with:checkIn', 'date', 'after:checkIn'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    protected function messages(): array
    {
        return [
            'checkIn.after_or_equal' => 'Tanggal check-in tidak boleh sebelum hari ini.',
            'checkOut.required_with' => 'Tanggal check-out wajib diisi bila check-in diisi.',
            'checkOut.after' => 'Tanggal check-out harus setelah tanggal check-in.',
            'guests.min' => 'Jumlah tamu minimal 1 orang.',
        ];
    }

    /** Validasi lalu arahkan ke halaman hasil pencarian. */
    public function search()
    {
        $this->validate();

        return $this->redirectRoute('hotels.index', array_filter([
            'kota' => $this->city,
            'masuk' => $this->checkIn,
            'keluar' => $this->checkOut,
            'tamu' => $this->guests,
        ], fn ($value) => $value !== '' && $value !== null), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.guest.search-form');
    }
}
