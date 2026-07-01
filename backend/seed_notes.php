<?php
// Script untuk seed 3 note awal ke database
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
    'title'           => 'Belajar Async Await JavaScript',
    'content'         => implode("\n", [
        '# Belajar Async/Await JavaScript',
        '',
        'Async/await adalah syntactic sugar di atas Promise yang membuat kode asynchronous terlihat seperti synchronous.',
        '',
        '## Contoh Dasar',
        '',
        '```javascript',
        'async function fetchData(url) {',
        '  try {',
        '    const response = await fetch(url);',
        '    const data = await response.json();',
        '    return data;',
        '  } catch (error) {',
        '    console.error(\'Fetch error:\', error);',
        '    throw error;',
        '  }',
        '}',
        '',
        '// Memanggil fungsi',
        'fetchData(\'https://api.example.com/users\')',
        '  .then(users => console.log(users))',
        '  .catch(err => console.error(err));',
        '```',
        '',
        '## Tips Penting',
        '',
        '- Selalu gunakan `try/catch` untuk error handling',
        '- `await` hanya bisa dipakai di dalam `async function`',
        '- Gunakan `Promise.all()` untuk parallel requests',
    ]),
    'deadline_at'     => '2026-07-05',
    'total_pomodoros' => 3,
]);

Note::create([
    'title'           => 'Tips Optimasi CSS Flexbox',
    'content'         => implode("\n", [
        '# Tips Optimasi CSS Flexbox',
        '',
        'Flexbox sangat powerful untuk layout satu dimensi.',
        '',
        '## Properti Utama',
        '',
        '- `display: flex` — aktifkan flex container',
        '- `justify-content` — alignment di main axis',
        '- `align-items` — alignment di cross axis',
        '- `flex-wrap: wrap` — memungkinkan items wrap',
        '',
        '## Contoh Centering',
        '',
        '```css',
        '.center-me {',
        '  display: flex;',
        '  justify-content: center;',
        '  align-items: center;',
        '  min-height: 100vh;',
        '}',
        '```',
    ]),
    'deadline_at'     => null,
    'total_pomodoros' => 1,
]);

Note::create([
    'title'           => 'Setup Tailwind Config',
    'content'         => implode("\n", [
        '# Setup Tailwind CSS Config',
        '',
        'Konfigurasi Tailwind untuk project modern.',
        '',
        '## Install',
        '',
        '```bash',
        'npm install -D tailwindcss postcss autoprefixer',
        'npx tailwindcss init -p',
        '```',
        '',
        '## tailwind.config.js',
        '',
        '```javascript',
        'module.exports = {',
        '  content: [\'./src/**/*.{js,jsx,ts,tsx,vue}\'],',
        '  darkMode: \'class\',',
        '  theme: {',
        '    extend: {',
        '      colors: {',
        '        brand: \'#14b8a6\',',
        '      },',
        '    },',
        '  },',
        '  plugins: [],',
        '};',
        '```',
    ]),
    'deadline_at'     => null,
    'total_pomodoros' => 0,
]);

echo "✅ Berhasil seed " . Note::count() . " notes ke database.\n";
