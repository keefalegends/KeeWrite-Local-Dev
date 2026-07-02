# KeeWrite 📝

> **Developer Productivity Hub** — Aplikasi full-stack untuk mencatat, fokus, dan melacak deadline. Dibangun dengan **Laravel 13** (Backend API) dan **Vue 3** (Frontend SPA).

---

## ✨ Fitur

| Fitur | Deskripsi |
|---|---|
| 📝 **Note Editor** | Editor Markdown dengan live preview & split-view |
| 🍅 **Pomodoro Timer** | Timer 25 menit dengan ring animasi, bisa di-link ke note |
| 📅 **Kalender Deadline** | Visualisasi deadline notes di kalender Juli 2026 |
| 💾 **Auto-save** | Debounced auto-save ke database 800ms setelah ketikan |
| 🗑️ **CRUD Notes** | Buat, edit, hapus note — tersimpan permanen di SQLite |
| 🔢 **Pomodoro Log** | Jumlah sesi fokus per note tersimpan di database |

---

## 🛠️ Tech Stack

### Backend
- **PHP 8.x** + **Laravel 13**
- **SQLite** (database lokal, tanpa konfigurasi tambahan)
- RESTful API dengan response format konsisten `{ success, data, message }`

### Frontend
- **Vue 3** (Composition API + `<script setup>`)
- **Vite** (dev server + proxy ke Laravel)
- **Tailwind CSS v4**
- **Axios** (HTTP client)
- **Lucide Vue** (icons)

---

## 📁 Struktur Project

```
KeeWrite/
├── backend/                          # Laravel 13 API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   └── NoteController.php   # CRUD + Pomodoro endpoint
│   │   └── Models/
│   │       └── Note.php
│   ├── database/
│   │   ├── database.sqlite           # Database utama
│   │   └── migrations/
│   ├── routes/
│   │   └── api.php                   # API routes
│   └── .env
│
└── frontend/                         # Vue 3 SPA
    ├── src/
    │   ├── components/
    │   │   └── DevProductivityHub.vue  # Komponen utama (all-in-one)
    │   ├── App.vue
    │   ├── main.js
    │   └── style.css
    └── vite.config.js                # Proxy /api → localhost:8000
```

---

## 🚀 Cara Menjalankan

### Prasyarat
- PHP 8.x
- Composer
- Node.js 18+
- npm

### 1. Clone repo
```bash
git clone https://github.com/keefalegends/KeeWrite.git
cd KeeWrite
```

### 2. Setup Backend (Laravel)
```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Buat file database SQLite
touch database/database.sqlite   # Linux/Mac
# atau di Windows:
# New-Item database/database.sqlite

# Jalankan migrasi
php artisan migrate

# (Opsional) Seed 3 note contoh
php seed_notes.php

# Jalankan server
php artisan serve
# → Server berjalan di http://127.0.0.1:8000
```

### 3. Setup Frontend (Vue 3)
```bash
cd frontend

# Install dependencies
npm install

# Jalankan dev server
npm run dev
# → App berjalan di http://localhost:5173
```

### 4. Buka di browser
```
http://localhost:5173
```

> ⚠️ **Penting:** Kedua server harus berjalan bersamaan. Jangan gunakan Laragon — jalankan `php artisan serve` langsung dari folder `backend/`.

---

## 🔌 API Endpoints

Base URL: `http://127.0.0.1:8000/api`

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/notes` | Ambil semua notes |
| `POST` | `/notes` | Buat note baru |
| `GET` | `/notes/{id}` | Ambil satu note |
| `PUT` | `/notes/{id}` | Update note |
| `DELETE` | `/notes/{id}` | Hapus note |
| `PUT` | `/notes/{id}/pomodoro` | Tambah 1 sesi pomodoro |

### Contoh Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Belajar Async Await JavaScript",
    "content": "# Belajar Async/Await...",
    "snippet": "Async/await adalah syntactic sugar...",
    "deadline_at": "2026-07-05",
    "total_pomodoros": 3,
    "created_at": "2026-07-01T09:00:00.000000Z",
    "updated_at": "2026-07-01T09:00:00.000000Z"
  },
  "message": "Note retrieved successfully."
}
```

---

## 🧭 Navigasi Aplikasi

| Menu | Fungsi |
|---|---|
| **Dashboard** | Pomodoro Timer — mulai sesi fokus, link ke note |
| **Notes** | Editor Markdown — buat & edit notes |
| **Calendar** | Lihat deadline notes di kalender |
| **Projects** | *(Coming soon)* |
| **Settings** | *(Coming soon)* |

---

## 📸 Screenshot

> Tambahkan screenshot di sini setelah deployment.

---

## 📄 Lisensi

MIT License — bebas digunakan untuk keperluan belajar dan PKL.

---

<div align="center">
  <sub>Dibuat dengan ❤️ untuk PKL — Desnet</sub>
</div>
