<?php
// Script untuk seed 1 note awal ke database
// Jalankan: php artisan tinker --execute="require 'seed_notes.php'"

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Note;

// Hanya seed jika belum ada data
if (Note::count() > 0) {
    echo "Notes sudah ada (" . Note::count() . " notes). Skip seeding.\n";
    exit(0);
}

Note::create([
    'title'           => 'Selamat Datang di KeeWrite!',
    'content'         => implode("\n", [
        '# Selamat Datang di KeeWrite! ⚡',
        '',
        'Ini adalah catatan contoh pertama Anda. Anda dapat mengedit catatan ini secara bebas menggunakan Editor Markdown di sebelah kiri dan melihat hasilnya langsung di panel Preview sebelah kanan.',
        '',
        '## Fitur Utama:',
        '- **Editor Split-View**: Tulis kode Markdown dan lihat hasilnya secara real-time.',
        '- **Pomodoro Timer**: Fokus belajar/bekerja menggunakan timer terintegrasi.',
        '- **Proyek & Kanban**: Kelola tugas dan status pekerjaan secara terstruktur.',
        '- **Kalender**: Atur deadline untuk catatan dan proyek secara interaktif.',
        '',
        '*Selamat produktif!*',
    ]),
    'deadline_at'     => null,
    'total_pomodoros' => 0,
]);

echo "✅ Berhasil seed " . Note::count() . " notes ke database.\n";
