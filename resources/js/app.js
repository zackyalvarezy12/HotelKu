import './bootstrap';

/*
 | Alpine TIDAK diimpor di sini.
 |
 | Livewire 3 sudah membawa Alpine dan menimpakan instansnya ke window.Alpine.
 | Bila Alpine juga di-import dan di-start sendiri di sini, terbentuk dua
 | instans ("Detected multiple instances of Alpine running") sehingga komponen
 | Alpine pada halaman Livewire tidak berfungsi. Alpine tersedia di semua
 | halaman melalui @livewireScripts pada layout.
 */
