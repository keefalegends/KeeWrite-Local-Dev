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
          <span class="text-xs font-bold text-white tracking-widest uppercase">KeeWrite</span>
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
      <div v-if="activeNav === 'Dashboard'" class="flex-1 overflow-y-auto p-6 md:p-8">
        <!-- Title -->
        <div class="flex items-center gap-3 mb-8 max-w-5xl mx-auto">
          <div class="w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center">
            <Timer :size="20" class="text-orange-400" />
          </div>
          <div>
            <h1 class="text-lg font-bold text-white">Dashboard</h1>
            <p class="text-xs text-zinc-500">Kelola fokus harian, tugas, dan tantangan belajarmu</p>
          </div>
        </div>

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-5xl mx-auto items-start">
          
          <!-- Left/Dominant Column: Pomodoro Timer & Log -->
          <div class="lg:col-span-7 space-y-6">
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
                <p class="text-[10px] text-zinc-500">✓ = mock selesai (untuk tes)</p>
              </div>
            </div>

            <!-- Session Log -->
            <div v-if="pomodoroLog.length > 0" class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 shadow-lg">
              <p class="text-[10px] text-zinc-500 uppercase tracking-wider mb-3 font-semibold">🍅 Log Sesi</p>
              <div class="space-y-2 max-h-36 overflow-y-auto">
                <p v-for="(entry, i) in [...pomodoroLog].reverse()" :key="i"
                   class="text-[11px] text-zinc-400 font-mono bg-zinc-950 px-3 py-1.5 rounded-lg border border-zinc-800">{{ entry }}</p>
              </div>
            </div>
          </div>

          <!-- Right Column: Daily Focus Checklist & LeetCode Daily -->
          <div class="lg:col-span-5 space-y-6">
            
            <!-- Widget 1: Daily Focus Checklist -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 shadow-xl space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <CheckCircle :size="16" class="text-orange-400" />
                  <span class="text-xs font-bold text-white uppercase tracking-wider">Daily Focus</span>
                </div>
                <span class="text-[10px] text-zinc-500 font-mono bg-zinc-800 px-2 py-0.5 rounded-full">
                  {{ dailyTasksCompletedCount }}/{{ dailyTasks.length }} Selesai
                </span>
              </div>

              <!-- Task input -->
              <form @submit.prevent="addDailyTask" class="flex gap-2">
                <input
                  v-model="newDailyTaskTitle"
                  placeholder="Tambah tugas hari ini..."
                  class="flex-1 bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-zinc-200 focus:outline-none focus:border-orange-500 transition-colors placeholder-zinc-600"
                />
                <button
                  type="submit"
                  :disabled="!newDailyTaskTitle.trim()"
                  class="w-8 h-8 rounded-xl bg-orange-500 hover:bg-orange-400 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-white transition-colors"
                >
                  <Plus :size="14" />
                </button>
              </form>

              <!-- Task list -->
              <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                <div
                  v-for="task in dailyTasks"
                  :key="task.id"
                  class="flex items-center justify-between gap-3 p-2.5 rounded-xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 transition-all group"
                >
                  <label class="flex items-center gap-2.5 cursor-pointer flex-1 min-w-0">
                    <input
                      type="checkbox"
                      v-model="task.done"
                      class="w-4 h-4 rounded border-zinc-700 text-teal-500 focus:ring-teal-500 focus:ring-offset-zinc-900 bg-zinc-800 cursor-pointer"
                    />
                    <span
                      :class="['text-xs truncate transition-all',
                               task.done ? 'line-through text-zinc-500 italic' : 'text-zinc-300 group-hover:text-white']"
                    >
                      {{ task.title }}
                    </span>
                  </label>
                  <button
                    @click="deleteDailyTask(task.id)"
                    class="opacity-0 group-hover:opacity-100 text-zinc-500 hover:text-red-400 w-6 h-6 rounded-lg flex items-center justify-center hover:bg-red-500/10 transition-all flex-shrink-0"
                  >
                    <Trash2 :size="12" />
                  </button>
                </div>

                <!-- Empty state -->
                <div v-if="dailyTasks.length === 0" class="text-center py-6 text-zinc-500 text-xs">
                  Belum ada tugas hari ini. Mulai dengan menulis satu tugas!
                </div>
              </div>
            </div>

            <!-- Widget 2: LeetCode Daily & Quote -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 shadow-xl space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Code2 :size="16" class="text-teal-400" />
                  <span class="text-xs font-bold text-white uppercase tracking-wider">LeetCode Daily</span>
                </div>
                <span class="text-[10px] bg-teal-500/10 text-teal-400 border border-teal-500/20 px-2.5 py-0.5 rounded-full font-medium">
                  Tantangan Harian
                </span>
              </div>
              
              <div class="bg-zinc-950 border border-zinc-800 rounded-xl p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <h4 class="text-xs font-semibold text-zinc-100 leading-snug truncate">{{ dailyLeetCode.title }}</h4>
                    <p class="text-[10px] text-zinc-500 mt-1 font-mono">{{ dailyLeetCode.category }}</p>
                  </div>
                  <span :style="{ color: dailyLeetCode.tagColor, backgroundColor: dailyLeetCode.tagColor + '15', borderColor: dailyLeetCode.tagColor + '30' }"
                        class="text-[9px] border px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider flex-shrink-0">
                    {{ dailyLeetCode.difficulty }}
                  </span>
                </div>
                
                <a :href="dailyLeetCode.link" target="_blank"
                   class="mt-4 w-full flex items-center justify-center gap-1.5 py-2 bg-teal-500 hover:bg-teal-400 text-white rounded-xl text-xs font-semibold transition-all">
                  Solve Challenge
                  <ExternalLink :size="12" />
                </a>
              </div>

              <!-- Quote of the Day -->
              <div class="border-t border-zinc-800/60 pt-4">
                <p class="text-xs text-zinc-400 italic leading-relaxed">
                  "{{ dailyQuote.text }}"
                </p>
                <p class="text-[10px] text-zinc-500 mt-1.5 text-right font-medium">— {{ dailyQuote.author }}</p>
              </div>
            </div>

          </div>

        </div>
      </div>

      <!-- ── VIEW: CALENDAR ── -->
      <div v-else-if="activeNav === 'Calendar'" class="flex-1 flex flex-col items-center justify-start p-8 overflow-y-auto">
        <div class="w-full max-w-lg">

          <!-- Title -->
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-teal-500/20 flex items-center justify-center">
              <CalendarIcon :size="20" class="text-teal-400" />
            </div>
            <div>
              <h1 class="text-lg font-bold text-white">Kalender</h1>
              <p class="text-xs text-zinc-500">{{ calMonthLabel }} — klik tanggal untuk atur deadline</p>
            </div>
          </div>

          <!-- Calendar card -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 shadow-2xl">

            <!-- Month nav header -->
            <div class="flex items-center justify-between mb-5">
              <div class="flex items-center gap-2">
                <button @click="prevMonth"
                        class="w-7 h-7 rounded-lg bg-zinc-800 hover:bg-zinc-700 flex items-center justify-center
                               text-zinc-400 hover:text-white transition-colors">
                  <ChevronLeft :size="14" />
                </button>
                <span class="text-base font-semibold text-white tracking-wide min-w-[120px] text-center">
                  {{ calMonthLabel }}
                </span>
                <button @click="nextMonth"
                        class="w-7 h-7 rounded-lg bg-zinc-800 hover:bg-zinc-700 flex items-center justify-center
                               text-zinc-400 hover:text-white transition-colors">
                  <ChevronRight :size="14" />
                </button>
              </div>
              <div class="flex items-center gap-3 text-xs text-zinc-500">
                <span class="flex items-center gap-1">
                  <span class="w-2.5 h-2.5 rounded-full bg-teal-500 inline-block"></span> Hari ini
                </span>
                <span class="flex items-center gap-1">
                  <span class="w-2.5 h-2.5 rounded-full bg-orange-400 inline-block"></span> Catatan
                </span>
                <span class="flex items-center gap-1">
                  <span class="w-2.5 h-2.5 rounded-full bg-violet-400 inline-block"></span> Proyek
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
              <div v-for="i in calLeadingBlanks" :key="`blank-${i}`" />
              <button
                v-for="day in calDaysInMonth"
                :key="day"
                @click="openDeadlineModal(day, 'note', null)"
                :class="['relative flex flex-col items-center justify-center rounded-xl py-2.5 text-sm font-medium transition-all cursor-pointer group',
                         isToday(day)
                           ? 'bg-teal-500 text-white font-bold shadow-lg shadow-teal-900/50'
                           : projectDeadlineDays.has(day)
                             ? 'bg-violet-500/15 text-violet-300 border border-violet-500/30 hover:bg-violet-500/25'
                             : noteDeadlineDays.has(day)
                               ? 'bg-orange-500/15 text-orange-300 border border-orange-500/30 hover:bg-orange-500/25'
                               : 'text-zinc-400 hover:bg-zinc-800 hover:text-white']"
                :title="`Atur deadline: ${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`"
              >
                {{ day }}
                <!-- deadline dots -->
                <div v-if="!isToday(day) && (noteDeadlineDays.has(day) || projectDeadlineDays.has(day))"
                     class="absolute bottom-1 flex gap-0.5 justify-center">
                  <span v-if="noteDeadlineDays.has(day)" class="w-1.5 h-1.5 rounded-full bg-orange-400" />
                  <span v-if="projectDeadlineDays.has(day)" class="w-1.5 h-1.5 rounded-full bg-violet-400" />
                </div>
                <!-- hover add hint -->
                <span
                  v-if="!noteDeadlineDays.has(day) && !projectDeadlineDays.has(day) && !isToday(day)"
                  class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <Plus :size="12" class="text-teal-400" />
                </span>
              </button>
            </div>
          </div>

          <!-- Upcoming Deadlines (gabungan notes + projects) -->
          <div class="mt-4 bg-zinc-900 border border-zinc-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
              <p class="text-[10px] text-zinc-500 uppercase tracking-wider font-semibold">Upcoming Deadlines</p>
              <span class="text-[10px] text-zinc-600">{{ upcomingDeadlines.length }} deadline</span>
            </div>

            <div v-if="upcomingDeadlines.length === 0" class="text-center py-6 text-zinc-600">
              <CalendarDays :size="28" class="mx-auto mb-2 opacity-30" />
              <p class="text-sm">Belum ada deadline.</p>
              <p class="text-xs mt-1 opacity-60">Klik tanggal di kalender untuk menambahkan.</p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="item in upcomingDeadlines"
                :key="item.id"
                class="flex flex-col p-3 rounded-xl bg-zinc-800/50 hover:bg-zinc-800
                       border transition-all group cursor-pointer"
                :class="isPastDeadline(item.deadline_at)
                          ? 'border-red-500/30 bg-red-500/5'
                          : isTodayDeadline(item.deadline_at)
                            ? 'border-orange-500/50 bg-orange-500/8'
                            : item.type === 'project'
                              ? 'border-violet-500/20 hover:border-violet-500/40 bg-violet-500/2'
                              : 'border-zinc-700/50'"
              >
                <div class="flex items-center gap-3">
                  <!-- Icon -->
                  <div
                    :class="['w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 text-sm font-bold',
                             isPastDeadline(item.deadline_at) ? 'bg-red-500/20 text-red-400' :
                             isTodayDeadline(item.deadline_at) ? 'bg-orange-500/20 text-orange-400' :
                             item.type === 'project' ? 'bg-violet-500/20 text-violet-400' : 'bg-orange-500/20 text-orange-400']"
                  >
                    <span>{{ item.emoji }}</span>
                  </div>

                  <!-- Info -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <p class="text-sm font-medium text-zinc-200 truncate group-hover:text-white transition-colors">
                        {{ item.title }}
                      </p>
                      <span v-if="item.type === 'project'"
                            class="text-[9px] bg-violet-500/20 text-violet-400 px-1.5 py-0.5 rounded-full font-semibold uppercase tracking-wider">
                        Proyek
                      </span>
                    </div>
                    <div class="flex items-center gap-2 mt-0.5">
                      <p :class="['text-[11px] font-mono',
                                 isPastDeadline(item.deadline_at) ? 'text-red-400' :
                                 isTodayDeadline(item.deadline_at) ? 'text-orange-400' : 'text-zinc-400']">
                        {{ item.deadline_at }}
                      </p>
                      <span v-if="isPastDeadline(item.deadline_at)"
                            class="text-[9px] bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded-full">Lewat</span>
                      <span v-else-if="isTodayDeadline(item.deadline_at)"
                            class="text-[9px] bg-orange-500/20 text-orange-400 px-1.5 py-0.5 rounded-full">Hari ini!</span>
                      <span v-else
                            class="text-[9px] text-zinc-600">{{ daysUntil(item.deadline_at) }} hari lagi</span>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center gap-1 flex-shrink-0">
                    <button
                      @click.stop="openDeadlineModal(null, item.type, item.dbId)"
                      class="w-7 h-7 rounded-lg bg-zinc-700 hover:bg-orange-500/20 flex items-center justify-center
                             text-zinc-500 hover:text-orange-400 transition-all"
                      title="Ubah deadline"
                    >
                      <Pencil :size="11" />
                    </button>
                    <button
                      v-if="item.type === 'note'"
                      @click.stop="selectNote(item.dbId); activeNav = 'Notes'"
                      class="text-[10px] text-zinc-500 hover:text-teal-400 transition-colors px-2 py-1
                             rounded-lg hover:bg-teal-500/10"
                    >
                      Buka →
                    </button>
                    <button
                      v-else-if="item.type === 'project'"
                      @click.stop="openProject(item.dbId); activeNav = 'Projects'"
                      class="text-[10px] text-zinc-500 hover:text-teal-400 transition-colors px-2 py-1
                             rounded-lg hover:bg-teal-500/10"
                    >
                      Buka →
                    </button>
                  </div>
                </div>

                <!-- Description / Keterangan (Opsional) -->
                <div v-if="item.keterangan"
                     class="mt-2 text-xs text-zinc-400 bg-zinc-900/50 border border-zinc-800/60 rounded-lg p-2 leading-relaxed flex items-start gap-1.5">
                  <span class="text-zinc-500 mt-0.5">ℹ</span>
                  <span class="italic text-zinc-400">"{{ item.keterangan }}"</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── DEADLINE MODAL ── -->
        <Transition name="modal">
          <div v-if="showDeadlineModal"
               class="fixed inset-0 z-50 flex items-center justify-center"
               @click.self="closeDeadlineModal">

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />

            <!-- Modal box -->
            <div class="relative w-full max-w-sm mx-4 bg-zinc-900 border border-zinc-700 rounded-2xl
                        shadow-2xl overflow-hidden animate-fadeIn">

              <!-- Header -->
              <div class="flex items-center gap-3 px-5 pt-5 pb-4 border-b border-zinc-800">
                <div class="w-9 h-9 rounded-xl bg-orange-500/20 flex items-center justify-center">
                  <CalendarDays :size="18" class="text-orange-400" />
                </div>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-white">
                    {{ modalClickedDate ? 'Atur Deadline untuk ' + modalDateLabel : 'Ubah Deadline' }}
                  </p>
                  <p class="text-[11px] text-zinc-500 mt-0.5">
                    Pilih target & tulis keterangan opsional
                  </p>
                </div>
                <button @click="closeDeadlineModal"
                        class="w-7 h-7 rounded-lg bg-zinc-800 hover:bg-zinc-700 flex items-center justify-center
                               text-zinc-400 hover:text-white transition-colors">
                  <X :size="14" />
                </button>
              </div>

              <!-- Body -->
              <div class="px-5 py-4 space-y-4">

                <!-- Target Type Selector -->
                <div>
                  <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1.5 block font-medium">
                    Tipe Target
                  </label>
                  <div class="flex gap-2 bg-zinc-950 p-1 rounded-xl border border-zinc-800">
                    <button
                      type="button"
                      @click="modalTargetType = 'note'"
                      :class="['flex-1 py-1.5 text-xs font-semibold rounded-lg transition-all',
                               modalTargetType === 'note' ? 'bg-zinc-800 text-white shadow' : 'text-zinc-500 hover:text-zinc-300']"
                    >
                      📝 Catatan (Note)
                    </button>
                    <button
                      type="button"
                      @click="modalTargetType = 'project'"
                      :class="['flex-1 py-1.5 text-xs font-semibold rounded-lg transition-all',
                               modalTargetType === 'project' ? 'bg-zinc-800 text-white shadow' : 'text-zinc-500 hover:text-zinc-300']"
                    >
                      🚀 Proyek (Project)
                    </button>
                  </div>
                </div>

                <!-- Note selector -->
                <div v-if="modalTargetType === 'note'">
                  <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1.5 block font-medium">
                    Pilih Catatan
                  </label>
                  <select
                    v-model="modalNoteId"
                    class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-sm
                           text-zinc-200 focus:outline-none focus:border-orange-500 transition-colors"
                  >
                    <option value="">— Pilih catatan —</option>
                    <option v-for="n in notes" :key="n.id" :value="n.id">
                      {{ n.title || 'Untitled Note' }}
                    </option>
                  </select>
                </div>

                <!-- Project selector -->
                <div v-if="modalTargetType === 'project'">
                  <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1.5 block font-medium">
                    Pilih Proyek
                  </label>
                  <select
                    v-model="modalProjectId"
                    class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-sm
                           text-zinc-200 focus:outline-none focus:border-orange-500 transition-colors"
                  >
                    <option value="">— Pilih proyek —</option>
                    <option v-for="p in projects" :key="p.id" :value="p.id">
                      {{ p.emoji }} {{ p.name }}
                    </option>
                  </select>
                </div>

                <!-- Date selector -->
                <div>
                  <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1.5 block font-medium">
                    Tanggal Deadline
                  </label>
                  <input
                    type="date"
                    v-model="modalDate"
                    class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-sm
                           text-orange-400 focus:outline-none focus:border-orange-500 cursor-pointer transition-colors"
                  />
                </div>

                <!-- Description Input (Keterangan) -->
                <div>
                  <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1.5 block font-medium">
                    Keterangan / Detail (Opsional)
                  </label>
                  <textarea
                    v-model="modalDescription"
                    placeholder="Tulis catatan tambahan untuk deadline ini..."
                    rows="2"
                    class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2 text-sm
                           text-zinc-200 focus:outline-none focus:border-orange-500 transition-colors resize-none placeholder-zinc-600"
                  />
                </div>
              </div>

              <!-- Footer -->
              <div class="flex gap-2 px-5 pb-5">
                <button
                  v-if="modalHasExistingDeadline"
                  @click="deleteDeadlineFromModal"
                  :disabled="isSavingDeadline"
                  class="px-4 py-2.5 rounded-xl border border-red-500/25 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-sm font-medium transition-all"
                >
                  Hapus
                </button>
                <button
                  @click="closeDeadlineModal"
                  class="flex-1 py-2.5 rounded-xl border border-zinc-700 text-zinc-400 text-sm
                         hover:bg-zinc-800 hover:text-zinc-200 transition-all"
                >
                  Batal
                </button>
                <button
                  @click="saveDeadlineFromModal"
                  :disabled="(!modalNoteId && modalTargetType === 'note') || (!modalProjectId && modalTargetType === 'project') || !modalDate || isSavingDeadline"
                  class="flex-1 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-400 text-white text-sm
                         font-medium transition-all disabled:opacity-40 disabled:cursor-not-allowed
                         flex items-center justify-center gap-2"
                >
                  <span v-if="isSavingDeadline" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  {{ isSavingDeadline ? 'Menyimpan...' : 'Simpan Deadline' }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
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
              <div class="flex-1 overflow-y-auto p-4 markdown-preview-body" v-html="renderedMarkdown" />
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

      <!-- ── VIEW: PROJECTS ── -->
      <div v-else-if="activeNav === 'Projects'" class="flex-1 flex flex-col min-h-0 overflow-hidden">

        <!-- ── STATE 1: Project List ── -->
        <div v-if="!activeProject" class="flex-1 overflow-y-auto p-8">

          <!-- Header -->
          <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-teal-500/20 flex items-center justify-center">
                <FolderKanban :size="20" class="text-teal-400" />
              </div>
              <div>
                <h1 class="text-lg font-bold text-white">Projects</h1>
                <p class="text-xs text-zinc-500">{{ projects.length }} proyek aktif</p>
              </div>
            </div>
            <button
              id="new-project-btn"
              @click="showNewProjectModal = true"
              class="flex items-center gap-2 px-4 py-2 bg-teal-500 hover:bg-teal-400 text-white
                     rounded-xl text-sm font-medium transition-all shadow-lg shadow-teal-900/30"
            >
              <Plus :size="16" />
              New Project
            </button>
          </div>

          <!-- Project Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
              v-for="proj in projects"
              :key="proj.id"
              @click="openProject(proj.id)"
              class="group bg-zinc-900 border border-zinc-800 rounded-2xl p-5 cursor-pointer
                     hover:border-zinc-600 hover:shadow-xl transition-all animate-fadeIn relative overflow-hidden"
            >
              <!-- Top color bar -->
              <div class="absolute top-0 left-0 right-0 h-0.5" :style="{background: proj.color}" />

              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg"
                       :style="{background: proj.color + '22'}">{{ proj.emoji }}</div>
                  <div>
                    <h3 class="text-sm font-bold text-white group-hover:text-teal-300 transition-colors">
                      {{ proj.name }}
                    </h3>
                    <p class="text-[10px] text-zinc-500 mt-0.5">
                      {{ doneTasks(proj) }}/{{ proj.tasks.length }} tasks selesai
                    </p>
                  </div>
                </div>
                <!-- Quick links -->
                <div class="flex gap-1" @click.stop>
                  <a v-if="proj.githubUrl" :href="proj.githubUrl" target="_blank"
                     class="w-7 h-7 rounded-lg bg-zinc-800 hover:bg-zinc-700 flex items-center justify-center
                            text-zinc-500 hover:text-white transition-all"
                     title="GitHub">
                    <GitBranch :size="13" />
                  </a>
                  <a v-if="proj.figmaUrl" :href="proj.figmaUrl" target="_blank"
                     class="w-7 h-7 rounded-lg bg-zinc-800 hover:bg-purple-500/30 flex items-center justify-center
                            text-zinc-500 hover:text-purple-400 transition-all"
                     title="Figma">
                    <Palette :size="13" />
                  </a>
                  <button @click.stop="deleteProject(proj.id)"
                          class="w-7 h-7 rounded-lg bg-zinc-800 hover:bg-red-500/20 flex items-center justify-center
                                 text-zinc-600 hover:text-red-400 transition-all opacity-0 group-hover:opacity-100">
                    <Trash2 :size="12" />
                  </button>
                </div>
              </div>

              <!-- Description -->
              <p class="text-xs text-zinc-500 mb-4 line-clamp-2 leading-relaxed">{{ proj.description }}</p>

              <!-- Tech tags -->
              <div class="flex flex-wrap gap-1 mb-4">
                <span v-for="tag in proj.tech" :key="tag"
                      class="text-[10px] px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-400 font-mono border border-zinc-700">
                  {{ tag }}
                </span>
              </div>

              <!-- Progress bar -->
              <div>
                <div class="flex justify-between items-center mb-1.5">
                  <span class="text-[10px] text-zinc-500">Progress</span>
                  <span class="text-[10px] font-mono" :style="{color: proj.color}">
                    {{ projectProgress(proj) }}%
                  </span>
                </div>
                <div class="w-full h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :style="{width: projectProgress(proj) + '%', background: proj.color}"
                  />
                </div>
              </div>
            </div>

            <!-- Add new card -->
            <div
              @click="showNewProjectModal = true"
              class="border-2 border-dashed border-zinc-800 rounded-2xl p-5 flex flex-col items-center
                     justify-center gap-3 cursor-pointer hover:border-teal-500/50 hover:bg-teal-500/5
                     transition-all text-zinc-600 hover:text-teal-400 group min-h-[200px]"
            >
              <div class="w-10 h-10 rounded-xl bg-zinc-800 group-hover:bg-teal-500/20 flex items-center
                          justify-center transition-colors">
                <Plus :size="20" />
              </div>
              <p class="text-sm font-medium">Tambah Project</p>
            </div>
          </div>
        </div>

        <!-- ── STATE 2: Project Detail (Kanban) ── -->
        <div v-else class="flex-1 flex flex-col min-h-0 overflow-hidden">

          <!-- Detail header -->
          <div class="flex-shrink-0 flex items-center gap-4 px-6 py-4 border-b border-zinc-800">
            <button
              @click="activeProject = null"
              class="flex items-center gap-2 text-xs text-zinc-500 hover:text-white px-3 py-1.5
                     rounded-lg hover:bg-zinc-800 transition-all"
            >
              <ArrowLeft :size="14" />
              Kembali
            </button>
            <div class="h-5 w-px bg-zinc-800" />
            <span class="text-lg font-bold text-white flex items-center gap-2">
              <span>{{ activeProject.emoji }}</span>
              {{ activeProject.name }}
            </span>
            <div class="flex flex-wrap gap-1 ml-2">
              <span v-for="tag in activeProject.tech" :key="tag"
                    class="text-[10px] px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-400 font-mono">
                {{ tag }}
              </span>
            </div>
            <div class="ml-auto flex items-center gap-3">
              <span class="text-xs text-zinc-500 font-mono">{{ projectProgress(activeProject) }}% selesai</span>
              <div class="w-20 h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all"
                     :style="{width: projectProgress(activeProject) + '%', background: activeProject.color}" />
              </div>
              <!-- Resources toggle -->
              <button
                @click="showResources = !showResources"
                :class="['flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-all',
                         showResources
                           ? 'bg-orange-500/15 border-orange-500/40 text-orange-400'
                           : 'border-zinc-700 text-zinc-500 hover:border-zinc-500 hover:text-zinc-300']"
              >
                <Bookmark :size="12" />
                Resources
                <span :class="['text-[10px] transition-transform duration-200', showResources ? 'rotate-180' : '']">
                  ▼
                </span>
              </button>
            </div>
          </div>

          <!-- Main content: Kanban + Bookmarks -->
          <div class="flex-1 flex min-h-0" style="overflow:hidden">

            <!-- Kanban Board -->
            <div class="flex-1 min-w-0 overflow-x-auto overflow-y-hidden p-6">
              <div class="flex gap-4 h-full" style="min-width:max-content">

              <!-- Kanban Column -->
              <div
                v-for="col in kanbanCols"
                :key="col.id"
                class="flex-shrink-0 w-72 flex flex-col bg-zinc-900/70 border border-zinc-800
                       rounded-2xl overflow-hidden"
              >
                <!-- Column header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-800">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :style="{background: col.color}" />
                    <span class="text-xs font-semibold text-zinc-300 uppercase tracking-wider">{{ col.label }}</span>
                    <span class="text-[10px] text-zinc-600 bg-zinc-800 px-1.5 py-0.5 rounded-full">
                      {{ tasksInCol(col.id).length }}
                    </span>
                  </div>
                  <button
                    @click="addTask(col.id)"
                    class="w-6 h-6 rounded-md bg-zinc-800 hover:bg-zinc-700 flex items-center justify-center
                           text-zinc-500 hover:text-white transition-colors"
                  >
                    <Plus :size="12" />
                  </button>
                </div>

                <!-- Task cards -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                  <div
                    v-for="task in tasksInCol(col.id)"
                    :key="task.id"
                    class="group bg-zinc-900 border border-zinc-800 hover:border-zinc-600 rounded-xl p-3
                           transition-all cursor-pointer animate-fadeIn"
                  >
                    <!-- Priority badge -->
                    <div class="flex items-start justify-between gap-2 mb-2">
                      <span
                        :class="['text-[9px] px-1.5 py-0.5 rounded-full font-semibold uppercase tracking-wider',
                                 task.priority === 'high'   ? 'bg-red-500/20 text-red-400' :
                                 task.priority === 'medium' ? 'bg-orange-500/20 text-orange-400' :
                                                              'bg-zinc-700 text-zinc-400']"
                      >{{ task.priority }}</span>
                      <button
                        @click="deleteTask(task.id)"
                        class="opacity-0 group-hover:opacity-100 w-5 h-5 rounded flex items-center justify-center
                               text-zinc-600 hover:text-red-400 transition-all"
                      >
                        <X :size="11" />
                      </button>
                    </div>

                    <p class="text-xs font-medium text-zinc-200 leading-snug mb-3">{{ task.title }}</p>

                    <!-- Move buttons -->
                    <div class="flex gap-1">
                      <button
                        v-for="mv in movesFor(task)"
                        :key="mv.to"
                        @click="moveTask(task.id, mv.to)"
                        :class="['flex-1 text-[9px] py-1 rounded-lg border font-medium transition-all',
                                 mv.to === 'done'
                                   ? 'border-teal-500/40 text-teal-400 hover:bg-teal-500/15'
                                   : mv.to === 'in-progress'
                                     ? 'border-orange-500/40 text-orange-400 hover:bg-orange-500/15'
                                     : 'border-zinc-600 text-zinc-500 hover:bg-zinc-800']"
                      >{{ mv.label }}</button>
                    </div>
                  </div>

                  <!-- Empty state -->
                  <div v-if="tasksInCol(col.id).length === 0"
                       class="text-center py-8 text-zinc-700 text-xs">
                    Tidak ada task
                  </div>
                </div><!-- end task list -->
              </div><!-- end kanban column -->
              </div><!-- end inner flex wrapper -->
            </div><!-- end kanban scroll area -->


            <!-- Resource Bookmarks sidebar (collapsible) -->
            <Transition name="resources-slide">
              <div v-if="showResources"
                   class="flex-shrink-0 w-64 flex flex-col border-l border-zinc-800 overflow-hidden">
              <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-800 flex-shrink-0">
                <div class="flex items-center gap-2">
                  <Bookmark :size="13" class="text-orange-400" />
                  <span class="text-xs font-semibold text-zinc-300 uppercase tracking-wider">Resources</span>
                </div>
              </div>

              <div class="flex-1 overflow-y-auto p-3 space-y-2">
                <a
                  v-for="bm in activeProject.bookmarks"
                  :key="bm.id"
                  :href="bm.url"
                  target="_blank"
                  class="group flex items-center gap-2 p-2.5 rounded-xl bg-zinc-900 border border-zinc-800
                         hover:border-orange-500/40 hover:bg-orange-500/5 transition-all block"
                  @click.right.prevent="deleteBookmark(bm.id)"
                >
                  <div class="w-7 h-7 rounded-lg bg-orange-500/20 flex items-center justify-center flex-shrink-0">
                    <Link2 :size="12" class="text-orange-400" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-zinc-300 group-hover:text-orange-300 truncate transition-colors">
                      {{ bm.title }}
                    </p>
                    <p class="text-[10px] text-zinc-600 truncate">{{ bm.url.replace('https://','') }}</p>
                  </div>
                  <ExternalLink :size="11" class="text-zinc-600 group-hover:text-orange-400 flex-shrink-0" />
                </a>

                <div v-if="activeProject.bookmarks.length === 0"
                     class="text-center py-6 text-zinc-700 text-xs">
                  Belum ada resource
                </div>
              </div>

              <!-- Add bookmark -->
              <div class="flex-shrink-0 p-3 border-t border-zinc-800">
                <div v-if="showBookmarkForm" class="space-y-2">
                  <input v-model="bmTitle" placeholder="Nama resource..."
                         class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-xs
                                text-zinc-200 focus:outline-none focus:border-orange-500 placeholder-zinc-600" />
                  <input v-model="bmUrl" placeholder="https://..."
                         class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-xs
                                text-zinc-200 focus:outline-none focus:border-orange-500 placeholder-zinc-600" />
                  <div class="flex gap-2">
                    <button @click="addBookmark"
                            class="flex-1 py-1.5 bg-orange-500 hover:bg-orange-400 text-white rounded-lg text-xs font-medium transition-colors">
                      Simpan
                    </button>
                    <button @click="showBookmarkForm = false; bmTitle = ''; bmUrl = ''"
                            class="flex-1 py-1.5 border border-zinc-700 text-zinc-400 hover:text-zinc-200 rounded-lg text-xs transition-colors">
                      Batal
                    </button>
                  </div>
                </div>
                <button v-else
                        @click="showBookmarkForm = true"
                        class="w-full flex items-center justify-center gap-2 py-2 rounded-xl border border-dashed
                               border-zinc-700 text-zinc-600 hover:border-orange-500/50 hover:text-orange-400
                               text-xs transition-all">
                  <Plus :size="12" /> Tambah Resource
                </button>
              </div><!-- add bookmark bar -->
            </div><!-- resources sidebar -->
            </Transition><!-- /resources-slide -->
          </div><!-- /kanban + resources row -->
        </div><!-- /detail state -->

        <!-- ── NEW PROJECT MODAL ── -->
        <Transition name="modal">
          <div v-if="showNewProjectModal"
               class="fixed inset-0 z-50 flex items-center justify-center"
               @click.self="showNewProjectModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />
            <div class="relative w-full max-w-md mx-4 bg-zinc-900 border border-zinc-700 rounded-2xl
                        shadow-2xl overflow-hidden animate-fadeIn">

              <!-- Modal header -->
              <div class="flex items-center justify-between px-5 pt-5 pb-4 border-b border-zinc-800">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-teal-500/20 flex items-center justify-center">
                    <FolderKanban :size="18" class="text-teal-400" />
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-white">New Project</p>
                    <p class="text-[11px] text-zinc-500">Buat proyek baru</p>
                  </div>
                </div>
                <button @click="showNewProjectModal = false"
                        class="w-7 h-7 rounded-lg bg-zinc-800 hover:bg-zinc-700 flex items-center justify-center
                               text-zinc-400 hover:text-white transition-colors">
                  <X :size="14" />
                </button>
              </div>

              <!-- Modal body -->
              <div class="px-5 py-4 space-y-4">
                <!-- Emoji + Name -->
                <div class="flex gap-3">
                  <div>
                    <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1 block">Ikon</label>
                    <input v-model="newProj.emoji"
                           class="w-14 h-10 bg-zinc-800 border border-zinc-700 rounded-xl text-center text-xl
                                  focus:outline-none focus:border-teal-500" />
                  </div>
                  <div class="flex-1">
                    <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1 block">Nama Project</label>
                    <input v-model="newProj.name" placeholder="My Awesome Project"
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-sm
                                  text-zinc-200 focus:outline-none focus:border-teal-500 placeholder-zinc-600" />
                  </div>
                </div>

                <!-- Description -->
                <div>
                  <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1 block">Deskripsi</label>
                  <textarea v-model="newProj.description" placeholder="Deskripsi singkat proyek..."
                            rows="2"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-sm
                                   text-zinc-200 focus:outline-none focus:border-teal-500 placeholder-zinc-600 resize-none" />
                </div>

                <!-- Tech tags -->
                <div>
                  <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1 block">Tech Stack (pisahkan koma)</label>
                  <input v-model="newProj.techStr" placeholder="Vue, Laravel, Tailwind"
                         class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-sm
                                text-zinc-200 focus:outline-none focus:border-teal-500 placeholder-zinc-600" />
                </div>

                <!-- Links -->
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1 block">GitHub URL</label>
                    <input v-model="newProj.githubUrl" placeholder="https://github.com/..."
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-xs
                                  text-zinc-200 focus:outline-none focus:border-teal-500 placeholder-zinc-600" />
                  </div>
                  <div>
                    <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1 block">Figma URL</label>
                    <input v-model="newProj.figmaUrl" placeholder="https://figma.com/..."
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-xs
                                  text-zinc-200 focus:outline-none focus:border-teal-500 placeholder-zinc-600" />
                  </div>
                </div>

                <!-- Color picker -->
                <div>
                  <label class="text-[10px] text-zinc-500 uppercase tracking-wider mb-2 block">Warna Aksen</label>
                  <div class="flex gap-2">
                    <button
                      v-for="c in accentColors"
                      :key="c"
                      @click="newProj.color = c"
                      :style="{background: c}"
                      :class="['w-7 h-7 rounded-lg transition-transform', newProj.color === c ? 'scale-125 ring-2 ring-white/30' : 'hover:scale-110']"
                    />
                  </div>
                </div>
              </div>

              <!-- Modal footer -->
              <div class="flex gap-2 px-5 pb-5">
                <button @click="showNewProjectModal = false"
                        class="flex-1 py-2.5 rounded-xl border border-zinc-700 text-zinc-400 text-sm
                               hover:bg-zinc-800 transition-all">
                  Batal
                </button>
                <button
                  @click="createProject"
                  :disabled="!newProj.name.trim()"
                  class="flex-1 py-2.5 rounded-xl bg-teal-500 hover:bg-teal-400 text-white text-sm font-medium
                         transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                >
                  Buat Project
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>

      <!-- ── VIEW: OTHER (placeholder) ── -->
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
  Moon, Sun, ChevronLeft, ChevronRight, X, Pencil,
  GitBranch, Palette, ArrowLeft, Bookmark, Link2, ExternalLink,
} from '@lucide/vue'

// ─── CONSTANTS ────────────────────────────────────────────────────
const POMO_DURATION      = 25 * 60
const circumference      = 2 * Math.PI * 54
const circumferenceLarge = 2 * Math.PI * 70
const TODAY              = new Date()
const TODAY_DAY          = TODAY.getDate()

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

// ─── DAILY FOCUS ──────────────────────────────────────────────────
const dailyTasks = ref(JSON.parse(localStorage.getItem('keewrite-daily-tasks') || '[]'))
const newDailyTaskTitle = ref('')

const dailyTasksCompletedCount = computed(() =>
  dailyTasks.value.filter(t => t.done).length
)

watch(dailyTasks, (newVal) => {
  localStorage.setItem('keewrite-daily-tasks', JSON.stringify(newVal))
}, { deep: true })

function addDailyTask() {
  if (!newDailyTaskTitle.value.trim()) return
  dailyTasks.value.push({
    id: Date.now(),
    title: newDailyTaskTitle.value.trim(),
    done: false
  })
  newDailyTaskTitle.value = ''
}

function deleteDailyTask(id) {
  dailyTasks.value = dailyTasks.value.filter(t => t.id !== id)
}

// ─── LEETCODE & QUOTES ────────────────────────────────────────────
const leetCodeChallenges = [
  { title: 'Two Sum', difficulty: 'Easy', link: 'https://leetcode.com/problems/two-sum/', tagColor: '#22c55e', category: 'Array / Hash Table' },
  { title: 'Longest Substring Without Repeating Characters', difficulty: 'Medium', link: 'https://leetcode.com/problems/longest-substring-without-repeating-characters/', tagColor: '#eab308', category: 'Sliding Window' },
  { title: 'Median of Two Sorted Arrays', difficulty: 'Hard', link: 'https://leetcode.com/problems/median-of-two-sorted-arrays/', tagColor: '#ef4444', category: 'Binary Search' },
  { title: 'Valid Parentheses', difficulty: 'Easy', link: 'https://leetcode.com/problems/valid-parentheses/', tagColor: '#22c55e', category: 'Stack' },
  { title: 'Merge k Sorted Lists', difficulty: 'Hard', link: 'https://leetcode.com/problems/merge-k-sorted-lists/', tagColor: '#ef4444', category: 'Divide & Conquer' },
  { title: 'Climbing Stairs', difficulty: 'Easy', link: 'https://leetcode.com/problems/climbing-stairs/', tagColor: '#22c55e', category: 'Dynamic Programming' },
]

const codingQuotes = [
  { text: "Talk is cheap. Show me the code.", author: "Linus Torvalds" },
  { text: "Programs must be written for people to read, and only incidentally for machines to execute.", author: "Harold Abelson" },
  { text: "Any fool can write code that a computer can understand. Good programmers write code that humans can understand.", author: "Martin Fowler" },
  { text: "First, solve the problem. Then, write the code.", author: "John Johnson" },
  { text: "Experience is the name everyone gives to their mistakes.", author: "Oscar Wilde" },
  { text: "Java is to JavaScript what car is to Carpet.", author: "Chris Heilmann" },
]

const dailyLeetCode = computed(() => {
  const index = TODAY_DAY % leetCodeChallenges.length
  return leetCodeChallenges[index]
})

const dailyQuote = computed(() => {
  const index = TODAY_DAY % codingQuotes.length
  return codingQuotes[index]
})

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
// navigasi bulan
const calYear  = ref(new Date().getFullYear())
const calMonth = ref(new Date().getMonth()) // 0-indexed

const calMonthLabel = computed(() =>
  new Date(calYear.value, calMonth.value, 1)
    .toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
)

const calDaysInMonth = computed(() =>
  new Date(calYear.value, calMonth.value + 1, 0).getDate()
)

// getDay() returns 0=Sun, need Mon-first (1=Mon, 2=Tue ... 0=Sun → 6)
const calLeadingBlanks = computed(() => {
  const d = new Date(calYear.value, calMonth.value, 1).getDay()
  return d === 0 ? 6 : d - 1 // convert Sun=0 → 6 blanks, Mon=1 → 0 blanks
})

// set note deadlines pada bulan yang sedang ditampilkan
const noteDeadlineDays = computed(() => {
  const ym = `${calYear.value}-${String(calMonth.value + 1).padStart(2, '0')}`
  const days = new Set()
  notes.value.forEach(n => {
    if (n.deadline_at && n.deadline_at.startsWith(ym)) {
      days.add(parseInt(n.deadline_at.split('-')[2], 10))
    }
  })
  return days
})

// set project deadlines pada bulan yang sedang ditampilkan
const projectDeadlineDays = computed(() => {
  const ym = `${calYear.value}-${String(calMonth.value + 1).padStart(2, '0')}`
  const days = new Set()
  projects.value.forEach(p => {
    if (p.deadline_at && p.deadline_at.startsWith(ym)) {
      days.add(parseInt(p.deadline_at.split('-')[2], 10))
    }
  })
  return days
})

function prevMonth() {
  if (calMonth.value === 0) { calMonth.value = 11; calYear.value-- }
  else calMonth.value--
}
function nextMonth() {
  if (calMonth.value === 11) { calMonth.value = 0; calYear.value++ }
  else calMonth.value++
}

function isToday(day) {
  return day === TODAY_DAY
    && calMonth.value === TODAY.getMonth()
    && calYear.value  === TODAY.getFullYear()
}

// Deadline helpers
function isPastDeadline(dateStr) {
  if (!dateStr) return false
  return new Date(dateStr) < new Date(TODAY.toDateString())
}
function isTodayDeadline(dateStr) {
  if (!dateStr) return false
  return dateStr === TODAY.toISOString().slice(0, 10)
}
function daysUntil(dateStr) {
  const diff = new Date(dateStr) - new Date(TODAY.toDateString())
  return Math.ceil(diff / 86400000)
}

// Note deadline descriptions/keterangan local storage persistence
const noteDeadlineDescriptions = ref(JSON.parse(localStorage.getItem('keewrite-note-deadline-desc') || '{}'))
watch(noteDeadlineDescriptions, (newVal) => {
  localStorage.setItem('keewrite-note-deadline-desc', JSON.stringify(newVal))
}, { deep: true })

// Semua notes dan projects dengan deadline, diurutkan terdekat dari hari ini
const upcomingDeadlines = computed(() => {
  const list = []
  notes.value.forEach(n => {
    if (n.deadline_at) {
      list.push({
        id: `note-${n.id}`,
        dbId: n.id,
        type: 'note',
        title: n.title || 'Untitled Note',
        deadline_at: n.deadline_at,
        keterangan: noteDeadlineDescriptions.value[n.id] || '',
        emoji: '📝',
      })
    }
  })
  projects.value.forEach(p => {
    if (p.deadline_at) {
      list.push({
        id: `project-${p.id}`,
        dbId: p.id,
        type: 'project',
        title: p.name || 'Untitled Project',
        deadline_at: p.deadline_at,
        keterangan: p.deadline_note || '',
        emoji: p.emoji || '🚀',
        color: p.color,
      })
    }
  })
  return list.sort((a, b) => a.deadline_at.localeCompare(b.deadline_at))
})

// ── DEADLINE MODAL ────────────────────────────────────────────────
const showDeadlineModal  = ref(false)
const modalClickedDate   = ref(null)   // null or number day
const modalDate          = ref('')     // YYYY-MM-DD string
const modalTargetType    = ref('note') // 'note' or 'project'
const modalNoteId        = ref('')     // target note id
const modalProjectId     = ref('')     // target project id
const modalDescription   = ref('')     // keterangan
const isSavingDeadline   = ref(false)

const modalDateLabel = computed(() => {
  if (!modalClickedDate.value) return ''
  return new Date(calYear.value, calMonth.value, modalClickedDate.value)
    .toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
})

function openDeadlineModal(day = null, targetType = 'note', targetId = null) {
  modalClickedDate.value = day
  modalTargetType.value  = targetType

  if (targetType === 'note') {
    modalNoteId.value = targetId ? String(targetId) : ''
    modalProjectId.value = ''
    if (targetId) {
      modalDescription.value = noteDeadlineDescriptions.value[targetId] || ''
    } else {
      modalDescription.value = ''
    }
  } else {
    modalProjectId.value = targetId ? String(targetId) : ''
    modalNoteId.value = ''
    if (targetId) {
      const proj = projects.value.find(p => p.id == targetId)
      modalDescription.value = proj?.deadline_note || ''
    } else {
      modalDescription.value = ''
    }
  }

  if (day) {
    modalDate.value = `${calYear.value}-${String(calMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
  } else {
    if (targetType === 'note') {
      const note = notes.value.find(n => n.id == targetId)
      modalDate.value = note?.deadline_at ?? ''
    } else {
      const proj = projects.value.find(p => p.id == targetId)
      modalDate.value = proj?.deadline_at ?? ''
    }
  }
  showDeadlineModal.value = true
}

function closeDeadlineModal() {
  showDeadlineModal.value = false
  modalClickedDate.value  = null
  modalNoteId.value       = ''
  modalProjectId.value    = ''
  modalDescription.value  = ''
  modalDate.value         = ''
}

async function saveDeadlineFromModal() {
  const dateVal = modalDate.value
  const descVal = modalDescription.value
  if (!dateVal) return

  isSavingDeadline.value = true
  try {
    if (modalTargetType.value === 'note') {
      if (!modalNoteId.value) return
      const response = await axios.put(`/api/notes/${modalNoteId.value}`, {
        deadline_at: dateVal,
      })
      const updated = response.data.data
      const idx = notes.value.findIndex(n => n.id == modalNoteId.value)
      if (idx !== -1) notes.value[idx].deadline_at = updated.deadline_at

      // Save description
      if (descVal.trim()) {
        noteDeadlineDescriptions.value[modalNoteId.value] = descVal.trim()
      } else {
        delete noteDeadlineDescriptions.value[modalNoteId.value]
      }
      noteDeadlineDescriptions.value = { ...noteDeadlineDescriptions.value }
    } else {
      if (!modalProjectId.value) return
      const proj = projects.value.find(p => p.id == modalProjectId.value)
      if (proj) {
        proj.deadline_at = dateVal
        proj.deadline_note = descVal.trim()
      }
    }
    closeDeadlineModal()
  } catch (err) {
    console.error('[DevHub] Gagal simpan deadline:', err)
  } finally {
    isSavingDeadline.value = false
  }
}

const modalHasExistingDeadline = computed(() => {
  if (modalTargetType.value === 'note') {
    if (!modalNoteId.value) return false
    const note = notes.value.find(n => n.id == modalNoteId.value)
    return !!note?.deadline_at
  } else {
    if (!modalProjectId.value) return false
    const proj = projects.value.find(p => p.id == modalProjectId.value)
    return !!proj?.deadline_at
  }
})

async function deleteDeadlineFromModal() {
  isSavingDeadline.value = true
  try {
    if (modalTargetType.value === 'note') {
      if (!modalNoteId.value) return
      const response = await axios.put(`/api/notes/${modalNoteId.value}`, {
        deadline_at: null,
      })
      const updated = response.data.data
      const idx = notes.value.findIndex(n => n.id == modalNoteId.value)
      if (idx !== -1) notes.value[idx].deadline_at = updated.deadline_at

      // Clear description
      delete noteDeadlineDescriptions.value[modalNoteId.value]
      noteDeadlineDescriptions.value = { ...noteDeadlineDescriptions.value }
    } else {
      if (!modalProjectId.value) return
      const proj = projects.value.find(p => p.id == modalProjectId.value)
      if (proj) {
        proj.deadline_at = null
        proj.deadline_note = ''
      }
    }
    closeDeadlineModal()
  } catch (err) {
    console.error('[DevHub] Gagal menghapus deadline:', err)
  } finally {
    isSavingDeadline.value = false
  }
}



// ─── PROJECTS ─────────────────────────────────────────────────────
const accentColors = ['#14b8a6','#f97316','#6366f1','#ec4899','#eab308','#3b82f6','#a855f7','#22c55e']

let _taskId = 100
function mkTask(title, status, priority = 'medium') {
  return { id: _taskId++, title, status, priority }
}

const initialProjects = [
  {
    id: 1,
    name: 'KeeWrite Dev Hub',
    description: 'Aplikasi workspace productivity untuk membantu menulis catatan, melacak proyek dengan papan Kanban, serta manajemen waktu fokus.',
    emoji: '⚡',
    color: '#14b8a6',
    tech: ['Vue 3', 'Laravel', 'SQLite', 'Tailwind'],
    githubUrl: '',
    figmaUrl: '',
    tasks: [
      mkTask('Buat rancangan database SQLite', 'todo', 'high'),
      mkTask('Integrasikan API notes ke frontend', 'todo', 'medium'),
    ],
    bookmarks: [
      { id: 1, title: 'Dokumentasi Project', url: 'https://github.com' },
    ],
    deadline_at: null,
    deadline_note: '',
  }
]

const projects = ref(JSON.parse(localStorage.getItem('keewrite-projects') || 'null') || initialProjects)

watch(projects, (newVal) => {
  localStorage.setItem('keewrite-projects', JSON.stringify(newVal))
}, { deep: true })


const activeProject = ref(null)

// Kanban columns config
const kanbanCols = [
  { id: 'todo',        label: 'Backlog / To-Do', color: '#71717a' },
  { id: 'in-progress', label: 'In Progress',     color: '#f97316' },
  { id: 'done',        label: 'Done',            color: '#14b8a6' },
]

const projectProgress = (proj) => {
  if (!proj.tasks.length) return 0
  return Math.round((proj.tasks.filter(t => t.status === 'done').length / proj.tasks.length) * 100)
}

const doneTasks = (proj) => proj.tasks.filter(t => t.status === 'done').length

function openProject(id) {
  activeProject.value = projects.value.find(p => p.id === id) ?? null
}

function deleteProject(id) {
  projects.value = projects.value.filter(p => p.id !== id)
  if (activeProject.value?.id === id) activeProject.value = null
}

// Kanban helpers
function tasksInCol(colId) {
  return activeProject.value?.tasks.filter(t => t.status === colId) ?? []
}

const moveMap = {
  'todo':        [{ to: 'in-progress', label: '▶ In Progress' }, { to: 'done', label: '✓ Done' }],
  'in-progress': [{ to: 'todo', label: '← Backlog' },            { to: 'done', label: '✓ Done' }],
  'done':        [{ to: 'in-progress', label: '↩ In Progress' }, { to: 'todo', label: '← Backlog' }],
}
function movesFor(task) { return moveMap[task.status] ?? [] }

function moveTask(taskId, toStatus) {
  const task = activeProject.value?.tasks.find(t => t.id === taskId)
  if (task) task.status = toStatus
}

function addTask(colId) {
  const title = prompt('Judul task baru:')
  if (!title?.trim()) return
  const priority = prompt('Priority (high/medium/low):', 'medium') || 'medium'
  activeProject.value.tasks.push(mkTask(title.trim(), colId, priority))
}

function deleteTask(taskId) {
  if (!activeProject.value) return
  activeProject.value.tasks = activeProject.value.tasks.filter(t => t.id !== taskId)
}

// Bookmarks
const showResources    = ref(false)
const showBookmarkForm = ref(false)
const bmTitle = ref('')
const bmUrl   = ref('')

function addBookmark() {
  if (!bmTitle.value.trim() || !bmUrl.value.trim()) return
  const url = bmUrl.value.startsWith('http') ? bmUrl.value : 'https://' + bmUrl.value
  activeProject.value.bookmarks.push({
    id: Date.now(),
    title: bmTitle.value.trim(),
    url,
  })
  showBookmarkForm.value = false
  bmTitle.value = ''
  bmUrl.value   = ''
}

function deleteBookmark(id) {
  if (!activeProject.value) return
  activeProject.value.bookmarks = activeProject.value.bookmarks.filter(b => b.id !== id)
}

// New Project Modal
const showNewProjectModal = ref(false)
const newProj = ref({
  name: '', description: '', techStr: '', githubUrl: '', figmaUrl: '',
  emoji: '🚀', color: '#14b8a6',
})

function createProject() {
  if (!newProj.value.name.trim()) return
  const id = Date.now()
  projects.value.push({
    id,
    name:        newProj.value.name.trim(),
    description: newProj.value.description.trim(),
    emoji:       newProj.value.emoji || '📁',
    color:       newProj.value.color,
    tech:        newProj.value.techStr.split(',').map(s => s.trim()).filter(Boolean),
    githubUrl:   newProj.value.githubUrl.trim(),
    figmaUrl:    newProj.value.figmaUrl.trim(),
    tasks:       [],
    bookmarks:   [],
  })
  newProj.value = { name: '', description: '', techStr: '', githubUrl: '', figmaUrl: '', emoji: '🚀', color: '#14b8a6' }
  showNewProjectModal.value = false
}

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
