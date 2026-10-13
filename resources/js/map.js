import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Ikon bawaan Leaflet diambil dari CSS relatif; dengan Vite perlu ditunjuk manual.
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
});

window.L = L;

/** Inisialisasi semua peta pada elemen [data-hotel-map]. */
function initMaps() {
    document.querySelectorAll('[data-hotel-map]').forEach((el) => {
        if (el.dataset.initialized === 'true') {
            return;
        }

        const lat = parseFloat(el.dataset.lat);
        const lng = parseFloat(el.dataset.lng);

        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            return;
        }

        const map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        L.marker([lat, lng]).addTo(map).bindPopup(el.dataset.label ?? '');

        el.dataset.initialized = 'true';
    });
}

document.addEventListener('DOMContentLoaded', initMaps);
// Dipanggil ulang setelah Livewire menavigasi (wire:navigate).
document.addEventListener('livewire:navigated', initMaps);
