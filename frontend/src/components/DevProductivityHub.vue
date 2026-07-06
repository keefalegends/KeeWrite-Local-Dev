<template>
  <div
    :data-theme="isDark ? 'dark' : 'light'"
    class="flex h-screen bg-zinc-950 text-zinc-100 font-sans antialiased overflow-hidden select-none"
  >

    <!-- ═══ LEFT SIDEBAR ═══ -->
    <aside
      :class="['flex-shrink-0 flex flex-col bg-zinc-900 border-r border-zinc-800 transition-all duration-300',
               sidebarOpen ? 'w-64' : 'w-16']"
    >
      <!-- Header -->
      <div class="flex items-center gap-2 px-4 py-4 border-b border-zinc-800 flex-shrink-0">
        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-teal-400 to-teal-600
                    flex items-center justify-center shadow-lg shadow-teal-900/50">
          <Zap :size="16" class="text-white" />
        </div>
        <div v-if="sidebarOpen" class="flex flex-col leading-tight overflow-hidden">
          <span class="text-xs font-bold text-white tracking-widest uppercase">Dev Hub</span>
          <span class="text-[9px] text-zinc-500 tracking-wider">Productivity Suite</span>
        </div>
        <button
          id="sidebar-toggle"
          @click="sidebarOpen = !sidebarOpen"
          class="ml-auto w-6 h-6 flex items-center justify-center rounded text-zinc-500
                 hover:text-white hover:bg-zinc-800 transition-colors flex-shrink-0"
        >
          <PanelLeftClose v-if="sidebarOpen" :size="14" />
          <PanelLeft v-else :size="14" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="px-2 py-3 space-y-0.5 flex-shrink-0">
        <button
          v-for="item in navItems"
          :key="item.name"
          :id="`nav-${item.name.toLowerCase()}`"
          @click="activeNav = item.name"
          :class="['w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all',
                   activeNav === item.name
                     ? 'bg-teal-500/15 text-teal-400 font-medium'
                     : 'text-zinc-500 hover:text-zinc-200 hover:bg-zinc-800']"
        >
          <component :is="item.icon" :size="16" class="flex-shrink-0" />
          <span v-if="sidebarOpen" class="truncate">{{ item.name }}</span>
        </button>
      </nav>

      <!-- Notes List (selalu tampil) -->
      <div v-if="sidebarOpen" class="flex-1 flex flex-col min-h-0 px-3 pt-1 pb-2 border-t border-zinc-800">
        <div class="flex items-center justify-between mb-2 flex-shrink-0">
          <span class="text-[10px] text-zinc-500 uppercase tracking-wider font-semibold">Notes</span>
          <button
            id="new-note-btn"
            @click="createNote"
            :disabled="isCreating"
            class="flex items-center gap-1 text-[10px] text-teal-400 hover:text-teal-300
                   hover:bg-teal-500/10 px-2 py-1 rounded-md transition-all
                   border border-teal-500/30 hover:border-teal-400/50 disabled:opacity-50"
          >
            <Plus :size="10" />
            {{ isCreating ? '...' : 'New' }}
          </button>
        </div>

        <div v-if="isLoading" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-14 bg-zinc-800 rounded-lg animate-pulse" />
        </div>

        <div v-else-if="loadError" class="text-center py-4 text-xs text-red-400 space-y-2">
          <WifiOff :size="20" class="mx-auto opacity-50" />
          <p class="text-[10px]">{{ loadError }}</p>
          <button @click="fetchNotes" class="text-teal-400 hover:underline text-[10px]">Retry</button>
        </div>

        <div v-else class="overflow-y-auto flex-1 space-y-1.5 pr-0.5">
          <div
            v-for="note in notes"
            :key="note.id"
            @click="selectNote(note.id)"
            :class="['group relative flex flex-col gap-1 p-3 rounded-lg cursor-pointer border transition-all animate-fadeIn',
                     selectedId === note.id
                       ? 'bg-teal-500/10 border-teal-500/40'
                       : 'border-zinc-800 hover:border-zinc-600 hover:bg-zinc-800/60']"
          >
            <div class="flex items-start justify-between gap-2">
              <span :class="['text-sm font-medium leading-snug truncate',
                             selectedId === note.id ? 'text-teal-300' : 'text-zinc-200']">
                {{ note.title || 'Untitled Note' }}
              </span>
              <button
                :id="`delete-note-${note.id}`"
                @click.stop="deleteNote(note.id)"
                class="opacity-0 group-hover:opacity-100 flex-shrink-0 w-5 h-5 rounded
                       flex items-center justify-center text-zinc-500 hover:text-red-400
                       hover:bg-red-400/10 transition-all"
              >
                <Trash2 :size="12" />
              </button>
            </div>
            <p class="text-[11px] text-zinc-500 line-clamp-1">{{ note.snippet || 'Empty note...' }}</p>
            <div class="flex items-center gap-2">
              <div v-if="note.deadline_at" class="flex items-center gap-1">
                <CalendarDays :size="10" class="text-orange-400" />
                <span class="text-[10px] text-orange-400">{{ note.deadline_at }}</span>
              </div>
              <div v-if="note.total_pomodoros > 0" class="flex items-center gap-1">
                <span class="text-[10px]">🍅</span>
                <span class="text-[10px] text-zinc-500">× {{ note.total_pomodoros }}</span>
              </div>
            </div>
          </div>
          <p v-if="!isLoading && notes.length === 0"
             class="text-center text-xs text-zinc-600 py-6">
            Belum ada notes.<br />Klik "+ New" untuk mulai.
          </p>
        </div>
      </div>

      <!-- Collapsed: icon shortcuts -->
      <div v-else class="px-2 py-2 border-t border-zinc-800 space-y-1 flex-shrink-0">
        <button @click="createNote" :disabled="isCreating"
                class="w-full flex justify-center py-2 rounded-lg text-teal-400 hover:bg-teal-500/10 transition-colors">
          <Plus :size="16" />
        </button>
        <button @click="toggleTimer"
                :class="['w-full flex justify-center py-2 rounded-lg transition-colors',
                         timerRunning ? 'text-orange-400 bg-orange-500/10' : 'text-zinc-500 hover:bg-zinc-800']">
          <Timer :size="16" />
        </button>
      </div>

      <!-- Footer -->
      <div class="mt-auto px-3 py-3 border-t border-zinc-800 flex-shrink-0">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-full bg-gradient-to-br from-teal-400 to-teal-600
                      flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0">
            D
          </div>
          <span v-if="sidebarOpen" class="text-xs text-zinc-400 truncate">Developer</span>
        </div>
      </div>
    </aside>

    <!-- ═══ MAIN PANEL ═══ -->
    <main class="flex-1 flex flex-col min-w-0 bg-zinc-950 overflow-hidden">

      <!-- ── VIEW: DASHBOARD (Pomodoro) ── -->
      <div v-if="activeNav === 'Dashboard'" class="flex-1 flex flex-col items-center justify-center p-8 overflow-y-auto">
        <div class="w-full max-w-md">
          <!-- Title -->
          <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center">
              <Timer :size="20" class="text-orange-400" />
            </div>
            <div>
              <h1 class="text-lg font-bold text-white">Pomodoro Timer</h1>
              <p class="text-xs text-zinc-500">Fokus 25 menit, istirahat 5 menit</p>
            </div>
          </div>

          <!-- Timer card -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 flex flex-col items-center gap-6 shadow-2xl">
            <!-- SVG Ring -->
            <div class="relative flex items-center justify-center" style="width:180px;height:180px">
              <svg width="180" height="180" viewBox="0 0 180 180"
                   :class="['rounded-full', timerRunning ? 'animate-pulseGlow' : '']">
                <circle cx="90" cy="90" r="70" fill="none" stroke="#27272a" stroke-width="10" />
                <circle
                  cx="90" cy="90" r="70" fill="none"
                  stroke="url(#pomoGradDash)" stroke-width="10" stroke-linecap="round"
                  :stroke-dasharray="circumferenceLarge"
                  :stroke-dashoffset="strokeOffsetLarge"
                  style="transform-origin:center;transform:rotate(-90deg);transition:stroke-dashoffset 1s linear"
                />
                <defs>
                  <linearGradient id="pomoGradDash" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#fb923c" />
                    <stop offset="100%" stop-color="#f97316" />
                  </linearGradient>
                </defs>
              </svg>
              <div class="absolute flex flex-col items-center">
                <span class="text-4xl font-bold font-mono text-orange-400 tabular-nums">
                  {{ formatTime(timerLeft) }}
                </span>
                <span class="text-xs text-zinc-500 mt-1">
                  {{ timerRunning ? 'Sedang fokus...' : timerLeft === POMO_DURATION ? 'Siap mulai' : 'Dijeda' }}
                </span>
              </div>
            </div>

            <!-- Controls -->
            <div class="flex items-center gap-4">
              <button id="pomo-reset" @click="resetTimer"
                      class="w-11 h-11 rounded-full bg-zinc-800 hover:bg-zinc-700 flex items-center justify-center
                             transition-colors border border-zinc-700" title="Reset">
                <RotateCcw :size="16" class="text-zinc-400" />
              </button>
              <button id="pomo-playpause" @click="toggleTimer"
                      class="w-16 h-16 rounded-full bg-orange-500 hover:bg-orange-400 flex items-center justify-center
                             transition-all shadow-xl shadow-orange-900/50">
                <Pause v-if="timerRunning" :size="24" class="text-white" />
                <Play  v-else              :size="24" class="text-white" />
              </button>
              <button id="pomo-mock-complete" @click="completePomodoro"
                      class="w-11 h-11 rounded-full bg-zinc-800 hover:bg-teal-700/80 flex items-center justify-center
                             transition-colors border border-zinc-700" title="Mock Selesai (tes)">
                <CheckCircle :size="16" class="text-teal-400" />
              </button>
            </div>

            <!-- Link to note -->
            <div class="w-full">
              <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1.5 block">
                Hubungkan ke Note
              </label>
              <select
                id="pomo-link-note"
                v-model="linkedNoteId"
                class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2 text-sm
                       text-zinc-300 focus:outline-none focus:border-orange-500 transition-colors"
              >
                <option value="">— Tidak ada —</option>
                <option v-for="n in notes" :key="n.id" :value="n.id">{{ n.title || 'Untitled' }}</option>
              </select>
            </div>

            <p class="text-[10px] text-zinc-600">✓ = mock selesai (untuk tes)</p>
          </div>

          <!-- Session Log -->
          <div v-if="pomodoroLog.length > 0" class="mt-4 bg-zinc-900 border border-zinc-800 rounded-xl p-4">
            <p class="text-[10px] text-zinc-500 uppercase tracking-wider mb-2">🍅 Log Sesi</p>
            <div class="space-y-1 max-h-32 overflow-y-auto">
              <p v-for="(entry, i) in [...pomodoroLog].reverse()" :key="i"
                 class="text-[11px] text-zinc-400 font-mono">{{ entry }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ── VIEW: CALENDAR ── -->
      <div v-else-if="activeNav === 'Calendar'" class="flex-1 flex flex-col items-center justify-start p-8 overflow-y-auto">
        <div class="w-full max-w-lg">
          <!-- Title -->
          <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-teal-500/20 flex items-center justify-center">
              <CalendarIcon :size="20" class="text-teal-400" />
            </div>
            <div>
              <h1 class="text-lg font-bold text-white">Kalender</h1>
              <p class="text-xs text-zinc-500">Juli 2026 — Deadline notes kamu</p>
            </div>
          </div>

          <!-- Calendar card -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 shadow-2xl">
            <!-- Month header -->
            <div class="flex items-center justify-between mb-5">
              <span class="text-base font-semibold text-white tracking-wide">Juli 2026</span>
              <div class="flex items-center gap-2 text-xs text-zinc-500">
                <span class="flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-teal-500 inline-block"></span> Hari ini
                </span>
                <span class="flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span> Deadline
                </span>
              </div>
            </div>

            <!-- Day headers -->
            <div class="grid grid-cols-7 gap-1 mb-2">
              <div v-for="d in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']" :key="d"
                   class="text-center text-[10px] font-semibold text-zinc-500 py-1 uppercase tracking-wider">
                {{ d }}
              </div>
            </div>

            <!-- Date grid -->
            <div class="grid grid-cols-7 gap-1">
              <div v-for="i in leadingBlanks" :key="`blank-${i}`" />
              <div
                v-for="day in 31"
                :key="day"
                :class="['relative flex flex-col items-center justify-center rounded-xl py-2.5 text-sm font-medium transition-all cursor-default',
                         day === TODAY_DAY
                           ? 'bg-teal-500 text-white font-bold shadow-lg shadow-teal-900/50'
                           : deadlineDays.has(day)
                             ? 'bg-orange-500/15 text-orange-300 border border-orange-500/30'
                             : 'text-zinc-400 hover:bg-zinc-800 hover:text-white']"
              >
                {{ day }}
                <span
                  v-if="deadlineDays.has(day) && day !== TODAY_DAY"
                  class="absolute bottom-1 w-1.5 h-1.5 rounded-full bg-orange-400"
                />
              </div>
            </div>
          </div>

          <!-- Deadline list -->
          <div class="mt-4 bg-zinc-900 border border-zinc-800 rounded-2xl p-5">
            <p class="text-[10px] text-zinc-500 uppercase tracking-wider mb-3 font-semibold">Upcoming Deadlines</p>
            <div v-if="notesWithDeadline.length === 0" class="text-center py-4 text-zinc-600 text-sm">
              Tidak ada deadline bulan ini
            </div>
            <div v-else class="space-y-2">
              <div v-for="note in notesWithDeadline" :key="note.id"
                   @click="selectNote(note.id); activeNav = 'Notes'"
                   class="flex items-center gap-3 p-3 rounded-xl bg-zinc-800/50 hover:bg-zinc-800
                          border border-zinc-700/50 cursor-pointer transition-all group">
                <div class="w-8 h-8 rounded-lg bg-orange-500/20 flex items-center justify-center flex-shrink-0">
                  <CalendarDays :size="14" class="text-orange-400" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-zinc-200 truncate group-hover:text-white transition-colors">
                    {{ note.title || 'Untitled Note' }}
                  </p>
                  <p class="text-[11px] text-orange-400">{{ note.deadline_at }}</p>
                </div>
                <span class="text-[10px] text-zinc-500 group-hover:text-zinc-300 transition-colors">Buka →</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── VIEW: NOTES (Editor) ── -->
      <template v-else-if="activeNav === 'Notes'">
        <div v-if="!selectedNote" class="flex-1 flex flex-col items-center justify-center text-zinc-600">
          <FileText :size="48" class="mb-4 opacity-30" />
          <p class="text-sm">Pilih atau buat note baru untuk memulai</p>
          <button @click="createNote"
                  class="mt-4 px-4 py-2 bg-teal-500/20 border border-teal-500/40 text-teal-400
                         rounded-lg text-sm hover:bg-teal-500/30 transition-all">
            + New Note
          </button>
        </div>

        <template v-else>
          <!-- Meta bar -->
          <div class="flex-shrink-0 px-5 pt-5 pb-3 border-b border-zinc-800">
            <input
              id="note-title-input"
              v-model="selectedNote.title"
              @input="scheduleSave"
              class="w-full bg-transparent text-xl font-bold text-white placeholder-zinc-600
                     focus:outline-none border-b border-transparent focus:border-teal-500/50 pb-1 transition-colors"
              placeholder="Judul note..."
            />
            <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-zinc-500">
              <div class="flex items-center gap-1">
                <Clock :size="12" class="text-zinc-600" />
                <span>Dibuat: {{ formatDate(selectedNote.created_at) }}</span>
              </div>
              <div class="flex items-center gap-1">
                <RefreshCw :size="12" class="text-zinc-600" />
                <span>Update: {{ formatDate(selectedNote.updated_at) }}</span>
              </div>
              <div class="flex items-center gap-1">
                <CalendarCheck :size="12" class="text-orange-400" />
                <input
                  id="note-deadline-input"
                  type="date"
                  v-model="selectedNote.deadline_at"
                  @change="scheduleSave"
                  class="bg-zinc-900 border border-zinc-700 rounded px-2 py-0.5 text-xs
                         text-orange-400 focus:outline-none focus:border-orange-500 cursor-pointer"
                />
              </div>
              <div class="flex items-center gap-1 ml-auto">
                <span v-if="isSaving" class="text-[10px] text-zinc-600 animate-pulse flex items-center gap-1">
                  <Save :size="10" /> Menyimpan...
                </span>
                <span v-else-if="lastSaved" class="text-[10px] text-teal-700 flex items-center gap-1">
                  <CheckCircle :size="10" /> Tersimpan
                </span>
                <span class="ml-2 text-zinc-500">🍅 {{ selectedNote.total_pomodoros }} sesi</span>
              </div>
            </div>
            <!-- View toggle -->
            <div class="flex gap-1 mt-3 bg-zinc-900 p-0.5 rounded-lg w-fit">
              <button
                v-for="[v, l] in [['editor','Kode'], ['split','Split'], ['preview','Preview']]"
                :key="v"
                :id="`view-${v}`"
                @click="editorView = v"
                :class="['px-3 py-1 rounded-md text-xs font-medium transition-all',
                         editorView === v ? 'bg-zinc-700 text-white shadow' : 'text-zinc-500 hover:text-zinc-300']"
              >{{ l }}</button>
            </div>
          </div>

          <!-- Editor / Preview -->
          <div class="flex-1 flex min-h-0">
            <div
              v-if="editorView === 'editor' || editorView === 'split'"
              :class="['flex flex-col', editorView === 'split' ? 'w-1/2 border-r border-zinc-800' : 'w-full']"
            >
              <div class="px-3 py-1.5 bg-zinc-900/50 border-b border-zinc-800 flex items-center gap-2">
                <Code2 :size="12" class="text-zinc-500" />
                <span class="text-[10px] text-zinc-500 uppercase tracking-wider">Markdown</span>
              </div>
              <textarea
                id="note-content-textarea"
                v-model="selectedNote.content"
                @input="scheduleSave"
                class="flex-1 bg-transparent text-sm font-mono text-zinc-300 p-4 resize-none
                       focus:outline-none leading-relaxed placeholder-zinc-700"
                placeholder="Ketik Markdown di sini..."
                spellcheck="false"
              />
            </div>
            <div
              v-if="editorView === 'preview' || editorView === 'split'"
              :class="['flex flex-col', editorView === 'split' ? 'w-1/2' : 'w-full']"
            >
              <div class="px-3 py-1.5 bg-zinc-900/50 border-b border-zinc-800 flex items-center gap-2">
                <Eye :size="12" class="text-teal-500" />
                <span class="text-[10px] text-zinc-500 uppercase tracking-wider">Preview</span>
              </div>
              <div class="flex-1 overflow-y-auto p-4" v-html="renderedMarkdown" />
            </div>
          </div>

          <!-- Pomodoro log strip -->
          <div v-if="pomodoroLog.length > 0" class="flex-shrink-0 border-t border-zinc-800 px-5 py-3">
            <p class="text-[10px] text-zinc-600 uppercase tracking-wider mb-1">🍅 Log Sesi</p>
            <div class="max-h-20 overflow-y-auto space-y-0.5">
              <p v-for="(entry, i) in [...pomodoroLog].reverse()" :key="i"
                 class="text-[10px] text-zinc-500 font-mono">{{ entry }}</p>
            </div>
          </div>
        </template>
      </template>

      <!-- ── VIEW: SETTINGS ── -->
      <div v-else-if="activeNav === 'Settings'" class="flex-1 overflow-y-auto p-8">
        <div class="max-w-lg mx-auto">
          <!-- Title -->
          <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-zinc-800 flex items-center justify-center">
              <Settings :size="20" class="text-zinc-400" />
            </div>
            <div>
              <h1 class="text-lg font-bold text-white">Settings</h1>
              <p class="text-xs text-zinc-500">Konfigurasi tampilan & preferensi</p>
            </div>
          </div>

          <!-- Appearance Card -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl mb-4">
            <div class="px-5 py-3 border-b border-zinc-800">
              <p class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Tampilan</p>
            </div>

            <!-- Theme toggle -->
            <div class="p-5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div :class="['w-9 h-9 rounded-xl flex items-center justify-center transition-colors',
                               isDark ? 'bg-zinc-800' : 'bg-amber-100']">
                    <Moon v-if="isDark" :size="18" class="text-teal-400" />
                    <Sun  v-else       :size="18" class="text-amber-500" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-zinc-200">Mode Tampilan</p>
                    <p class="text-xs text-zinc-500 mt-0.5">
                      {{ isDark ? 'Dark Mode — latar gelap, nyaman di malam hari' : 'Light Mode — latar terang, nyaman di siang hari' }}
                    </p>
                  </div>
                </div>

                <!-- Toggle switch -->
                <button
                  id="theme-toggle"
                  @click="toggleTheme"
                  :class="['relative inline-flex w-14 h-7 items-center rounded-full transition-colors duration-300 focus:outline-none flex-shrink-0',
                           isDark ? 'bg-teal-600' : 'bg-amber-400']">
                  <span
                    :class="['absolute w-5 h-5 bg-white rounded-full shadow-md transition-transform duration-300',
                             isDark ? 'translate-x-1' : 'translate-x-8']"
                  />
                </button>
              </div>

              <!-- Mode preview chips -->
              <div class="flex gap-2 mt-5">
                <button
                  @click="setTheme(true)"
                  :class="['flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border text-xs font-medium transition-all',
                           isDark
                             ? 'bg-teal-500/15 border-teal-500/50 text-teal-400'
                             : 'border-zinc-700 text-zinc-500 hover:border-zinc-600']">
                  <Moon :size="14" />
                  Dark Mode
                  <span v-if="isDark" class="ml-1 text-[9px] bg-teal-500/30 text-teal-300 px-1.5 py-0.5 rounded-full">Aktif</span>
                </button>
                <button
                  @click="setTheme(false)"
                  :class="['flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border text-xs font-medium transition-all',
                           !isDark
                             ? 'bg-amber-400/15 border-amber-400/50 text-amber-500'
                             : 'border-zinc-700 text-zinc-500 hover:border-zinc-600']">
                  <Sun :size="14" />
                  Light Mode
                  <span v-if="!isDark" class="ml-1 text-[9px] bg-amber-400/30 text-amber-600 px-1.5 py-0.5 rounded-full">Aktif</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Editor Card -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl mb-4">
            <div class="px-5 py-3 border-b border-zinc-800">
              <p class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Editor</p>
            </div>
            <div class="p-5 space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-zinc-200">Default View</p>
                  <p class="text-xs text-zinc-500 mt-0.5">Tampilan awal editor saat membuka note</p>
                </div>
                <select
                  v-model="editorView"
                  class="bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-xs text-zinc-300
                         focus:outline-none focus:border-teal-500 cursor-pointer">
                  <option value="editor">Kode</option>
                  <option value="split">Split</option>
                  <option value="preview">Preview</option>
                </select>
              </div>
            </div>
          </div>

          <!-- About Card -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-5 py-3 border-b border-zinc-800">
              <p class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Tentang</p>
            </div>
            <div class="p-5 space-y-2 text-sm text-zinc-400">
              <div class="flex justify-between">
                <span>Aplikasi</span>
                <span class="text-zinc-300 font-medium">KeeWrite Dev Hub</span>
              </div>
              <div class="flex justify-between">
                <span>Versi</span>
                <span class="text-zinc-300 font-mono">1.0.0</span>
              </div>
              <div class="flex justify-between">
                <span>Backend</span>
                <span class="text-zinc-300">Laravel 13 + SQLite</span>
              </div>
              <div class="flex justify-between">
                <span>Frontend</span>
                <span class="text-zinc-300">Vue 3 + Vite + Tailwind</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── VIEW: PROJECTS (placeholder) ── -->
      <div v-else class="flex-1 flex flex-col items-center justify-center text-zinc-600">
        <component :is="activeNavItem?.icon" :size="48" class="mb-4 opacity-20" />
        <p class="text-sm font-medium">{{ activeNav }}</p>
        <p class="text-xs mt-1 opacity-60">Coming soon...</p>
      </div>

    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import {
  Zap, PanelLeftClose, PanelLeft,
  LayoutDashboard, FileText, FolderKanban, Calendar as CalendarIcon, Settings,
  Plus, Trash2, CalendarDays, Clock, RefreshCw, CalendarCheck,
  Save, CheckCircle, Code2, Eye, Timer, RotateCcw, Pause, Play, WifiOff,
  Moon, Sun,
} from '@lucide/vue'

// ─── CONSTANTS ────────────────────────────────────────────────────
const POMO_DURATION    = 25 * 60
const circumference    = 2 * Math.PI * 54   // ring kecil (sidebar)
const circumferenceLarge = 2 * Math.PI * 70 // ring besar (dashboard)
const TODAY_DAY        = new Date().getDate()
const leadingBlanks    = new Date(2026, 6, 1).getDay() // Juli 2026 = Rabu = 3

// ─── THEME ────────────────────────────────────────────────────────
const isDark = ref(localStorage.getItem('keewrite-theme') !== 'light')

function toggleTheme() {
  isDark.value = !isDark.value
  localStorage.setItem('keewrite-theme', isDark.value ? 'dark' : 'light')
}

function setTheme(dark) {
  isDark.value = dark
  localStorage.setItem('keewrite-theme', dark ? 'dark' : 'light')
}

// ─── NAVIGATION ───────────────────────────────────────────────────
const sidebarOpen = ref(true)
const activeNav   = ref('Notes')

const navItems = [
  { name: 'Dashboard', icon: LayoutDashboard },
  { name: 'Notes',     icon: FileText },
  { name: 'Projects',  icon: FolderKanban },
  { name: 'Calendar',  icon: CalendarIcon },
  { name: 'Settings',  icon: Settings },
]
const activeNavItem = computed(() => navItems.find(i => i.name === activeNav.value))

// ─── NOTES STATE ──────────────────────────────────────────────────
const notes      = ref([])
const selectedId = ref(null)
const isLoading  = ref(false)
const isCreating = ref(false)
const loadError  = ref(null)

const selectedNote = computed(() =>
  notes.value.find(n => n.id === selectedId.value) ?? null
)

const notesWithDeadline = computed(() =>
  notes.value
    .filter(n => n.deadline_at && n.deadline_at.startsWith('2026-07'))
    .sort((a, b) => a.deadline_at.localeCompare(b.deadline_at))
)

// ─── AUTO-SAVE ────────────────────────────────────────────────────
const pomodoroLog = ref([])
const isSaving    = ref(false)
const lastSaved   = ref(false)
let   saveTimer   = null

function scheduleSave() {
  clearTimeout(saveTimer)
  lastSaved.value = false
  saveTimer = setTimeout(saveCurrentNote, 800)
}

async function saveCurrentNote() {
  if (!selectedNote.value) return
  isSaving.value = true
  try {
    const response = await axios.put(`/api/notes/${selectedNote.value.id}`, {
      title:       selectedNote.value.title,
      content:     selectedNote.value.content,
      deadline_at: selectedNote.value.deadline_at || null,
    })
    const updatedNote = response.data.data
    const idx = notes.value.findIndex(n => n.id === updatedNote.id)
    if (idx !== -1) {
      notes.value[idx].updated_at  = updatedNote.updated_at
      notes.value[idx].deadline_at = updatedNote.deadline_at
    }
    lastSaved.value = true
    setTimeout(() => { lastSaved.value = false }, 2000)
  } catch (err) {
    console.error('[DevHub] Gagal menyimpan:', err)
  } finally {
    isSaving.value = false
  }
}

// ─── EDITOR ───────────────────────────────────────────────────────
const editorView = ref('split')
const renderedMarkdown = computed(() =>
  selectedNote.value ? renderMarkdown(selectedNote.value.content ?? '') : ''
)

// ─── POMODORO ─────────────────────────────────────────────────────
const timerLeft    = ref(POMO_DURATION)
const timerRunning = ref(false)
const linkedNoteId = ref('')
let   timerInterval = null

const strokeOffset = computed(() => {
  const p = (POMO_DURATION - timerLeft.value) / POMO_DURATION
  return circumference * (1 - p)
})
const strokeOffsetLarge = computed(() => {
  const p = (POMO_DURATION - timerLeft.value) / POMO_DURATION
  return circumferenceLarge * (1 - p)
})

watch(selectedId, id => { if (id) linkedNoteId.value = String(id) })

function toggleTimer() { timerRunning.value = !timerRunning.value }
function resetTimer()  { timerRunning.value = false; timerLeft.value = POMO_DURATION }

async function completePomodoro() {
  timerRunning.value = false
  timerLeft.value    = POMO_DURATION
  const targetId = linkedNoteId.value || selectedId.value
  if (!targetId) return
  try {
    const response   = await axios.put(`/api/notes/${targetId}/pomodoro`)
    const updatedNote = response.data.data
    const note = notes.value.find(n => n.id == targetId)
    if (note && updatedNote) {
      note.total_pomodoros = updatedNote.total_pomodoros
      note.updated_at      = updatedNote.updated_at
    }
    const ts = new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
    pomodoroLog.value.push(`${ts} — Sesi fokus selesai 🍅`)
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('🍅 Pomodoro Selesai!', { body: 'Kerja bagus! Waktunya istirahat.' })
    }
  } catch (err) {
    console.error('[DevHub] Gagal kirim Pomodoro:', err)
  }
}

watch(timerRunning, running => {
  if (running) {
    timerInterval = setInterval(() => {
      if (timerLeft.value <= 1) { clearInterval(timerInterval); completePomodoro() }
      else timerLeft.value--
    }, 1000)
  } else {
    clearInterval(timerInterval)
  }
})

// ─── CALENDAR ─────────────────────────────────────────────────────
const deadlineDays = computed(() => {
  const days = new Set()
  notes.value.forEach(n => {
    if (n.deadline_at && n.deadline_at.startsWith('2026-07')) {
      days.add(parseInt(n.deadline_at.split('-')[2], 10))
    }
  })
  return days
})

// ─── API: FETCH ───────────────────────────────────────────────────
async function fetchNotes() {
  isLoading.value = true
  loadError.value = null
  try {
    const response  = await axios.get('/api/notes')
    notes.value = response.data.data ?? []
    if (notes.value.length > 0 && !selectedId.value) {
      selectedId.value = notes.value[0].id
    }
  } catch (err) {
    console.error('[DevHub] Gagal fetch notes:', err)
    loadError.value = 'Tidak bisa terhubung ke API. Pastikan Laravel running di port 8000.'
  } finally {
    isLoading.value = false
  }
}

// ─── API: CREATE ──────────────────────────────────────────────────
async function createNote() {
  isCreating.value = true
  try {
    const response = await axios.post('/api/notes', { title: '', content: '' })
    const newNote  = response.data.data
    notes.value.unshift(newNote)
    selectedId.value = newNote.id
    activeNav.value  = 'Notes'
    pomodoroLog.value = []
  } catch (err) {
    console.error('[DevHub] Gagal buat note:', err)
    alert('Gagal membuat note. Pastikan backend Laravel running di port 8000.')
  } finally {
    isCreating.value = false
  }
}

// ─── API: DELETE ──────────────────────────────────────────────────
async function deleteNote(id) {
  const backup = [...notes.value]
  const backupId = selectedId.value
  notes.value = notes.value.filter(n => n.id !== id)
  if (selectedId.value === id) {
    selectedId.value = notes.value[0]?.id ?? null
    pomodoroLog.value = []
  }
  try {
    await axios.delete(`/api/notes/${id}`)
  } catch (err) {
    console.error('[DevHub] Gagal hapus note:', err)
    notes.value = backup
    selectedId.value = backupId
  }
}

// ─── HELPERS ──────────────────────────────────────────────────────
function selectNote(id) {
  clearTimeout(saveTimer)
  selectedId.value  = id
  pomodoroLog.value = []
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatTime(seconds) {
  return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`
}

// ─── MARKDOWN RENDERER ────────────────────────────────────────────
function syntaxHighlight(code) {
  const kw  = /\b(async|await|function|return|const|let|var|if|else|try|catch|throw|new|class|import|export|from|default|for|while|of|in|typeof|null|undefined|true|false|this|super)\b/g
  const str = /(["'`])(?:(?=(\\?))\2[\s\S])*?\1/g
  const cmt = /(\/\/[^\n]*)|(\/\*[\s\S]*?\*\/)/g
  const num = /\b(\d+\.?\d*)\b/g
  const fn  = /\b([a-zA-Z_$][a-zA-Z0-9_$]*)\s*(?=\()/g
  return code
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(cmt, m => `<span style="color:#546e7a;font-style:italic">${m}</span>`)
    .replace(str, m => `<span style="color:#c3e88d">${m}</span>`)
    .replace(kw,  m => `<span style="color:#c792ea">${m}</span>`)
    .replace(fn,  (_, n) => `<span style="color:#82aaff">${n}</span>(`)
    .replace(num, m => `<span style="color:#f78c6c">${m}</span>`)
}

function inlineFormat(t) {
  return t
    .replace(/\*\*(.+?)\*\*/g, '<strong style="color:#fff">$1</strong>')
    .replace(/\*(.+?)\*/g,     '<em style="color:#e4e4e7">$1</em>')
    .replace(/`(.+?)`/g,       '<code style="background:#27272a;color:#fb923c;padding:0 4px;border-radius:3px;font-family:monospace;font-size:12px">$1</code>')
    .replace(/\[(.+?)\]\((.+?)\)/g,'<a href="$2" style="color:#2dd4bf;text-decoration:underline" target="_blank">$1</a>')
}

function renderMarkdown(md) {
  if (!md) return ''
  const lines = md.split('\n')
  let html = '', inCode = false, buf = []
  for (const line of lines) {
    if (line.startsWith('```')) {
      if (!inCode) { inCode = true; buf = [] }
      else {
        html += `<pre style="background:#1e1e2e;border:1px solid #3f3f46;border-radius:8px;padding:16px;margin:12px 0;overflow-x:auto;font-family:'Fira Code',monospace;font-size:13px;line-height:1.6"><code>${syntaxHighlight(buf.join('\n'))}</code></pre>`
        inCode = false; buf = []
      }
      continue
    }
    if (inCode) { buf.push(line); continue }
    if (line.startsWith('### ')) { html += `<h3 style="color:#2dd4bf;font-size:14px;font-weight:600;margin:16px 0 4px">${line.slice(4)}</h3>`; continue }
    if (line.startsWith('## '))  { html += `<h2 style="color:#5eead4;font-size:16px;font-weight:600;margin:20px 0 4px;border-bottom:1px solid #3f3f46;padding-bottom:4px">${line.slice(3)}</h2>`; continue }
    if (line.startsWith('# '))   { html += `<h1 style="color:#fff;font-size:20px;font-weight:700;margin:12px 0 8px">${line.slice(2)}</h1>`; continue }
    if (line.startsWith('- '))   { html += `<li style="margin-left:16px;list-style:disc;color:#a1a1aa;font-size:13px;line-height:1.7">${inlineFormat(line.slice(2))}</li>`; continue }
    if (line.startsWith('> '))   { html += `<blockquote style="border-left:3px solid #14b8a6;padding-left:12px;color:#71717a;font-style:italic;margin:8px 0">${line.slice(2)}</blockquote>`; continue }
    if (line === '---')           { html += `<hr style="border-color:#3f3f46;margin:12px 0"/>`; continue }
    if (line.trim() === '')       { html += `<br/>`; continue }
    html += `<p style="color:#a1a1aa;font-size:13px;line-height:1.7;margin:4px 0">${inlineFormat(line)}</p>`
  }
  return html
}

// ─── LIFECYCLE ────────────────────────────────────────────────────
onMounted(() => {
  fetchNotes()
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission()
  }
})

onBeforeUnmount(() => {
  clearInterval(timerInterval)
  clearTimeout(saveTimer)
})
</script>
