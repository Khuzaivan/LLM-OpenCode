<!doctype html>
<html lang="id" class="h-full" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>OpenCode Chat</title>
  
  <script>
    // Inisialisasi tema sebelum render
    document.documentElement.dataset.theme = localStorage.getItem('opencode-theme') || 'dark';
  </script>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/styles/github-dark.min.css">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['Plus Jakarta Sans', 'sans-serif'],
            mono: ['Fira Code', 'monospace'],
          }
        }
      }
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/index.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
  
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  
  <style>
    :root {
      --accent: 79, 70, 229;          /* #4f46e5 */
      --accent-hover: 67, 56, 202;    /* #4338ca */
      --ok: 16, 185, 129;             /* #10b981 */
      --err: 239, 68, 68;             /* #ef4444 */
    }

    [data-theme="dark"] {
      --bg: #07090e;
      --side: #0b0d16;
      --surface: #121520;
      --surface-hover: #191e2e;
      --muted: #1c2032;
      --border: #232a3f;
      --text: #f3f4f6;
      --sub: #9ca3af;
      --faint: #6b7280;
      --user-bubble: #1f1f45;
      --shadow: rgba(0, 0, 0, 0.4);
    }

    [data-theme="light"] {
      --bg: #f8fafc;
      --side: #ffffff;
      --surface: #ffffff;
      --surface-hover: #f1f5f9;
      --muted: #e2e8f0;
      --border: #cbd5e1;
      --text: #0f172a;
      --sub: #475569;
      --faint: #94a3b8;
      --user-bubble: #e0e7ff;
      --shadow: rgba(148, 163, 184, 0.1);
    }

    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Plus Jakarta Sans', sans-serif;
      overflow: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: transparent;
    }
    ::-webkit-scrollbar-thumb {
      background: var(--border);
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: var(--faint);
    }

    /* Glassmorphism Classes */
    .glass-card {
      background: var(--surface);
      border: 1px solid var(--border);
      box-shadow: 0 4px 30px var(--shadow);
    }

    /* Dynamic hover animations */
    .btn-hover {
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-hover:hover {
      transform: translateY(-1px);
    }
    .btn-hover:active {
      transform: translateY(0);
    }

    /* Markdown styling */
    .markdown-body p {
      margin-bottom: 0.75rem;
    }
    .markdown-body p:last-child {
      margin-bottom: 0;
    }
    .markdown-body pre {
      margin: 0.75rem 0;
      padding: 0.875rem;
      overflow-x: auto;
      background-color: var(--muted);
      border: 1px solid var(--border);
      border-radius: 0.5rem;
      font-family: 'Fira Code', monospace;
    }
    .markdown-body code {
      font-family: 'Fira Code', monospace;
      font-size: 0.875rem;
      padding: 0.125rem 0.25rem;
      background-color: var(--muted);
      border-radius: 0.25rem;
    }
    .markdown-body pre code {
      padding: 0;
      background-color: transparent;
      border-radius: 0;
    }
    .markdown-body ul, .markdown-body ol {
      margin-bottom: 0.75rem;
      padding-left: 1.5rem;
    }
    .markdown-body ul {
      list-style-type: disc;
    }
    .markdown-body ol {
      list-style-type: decimal;
    }
    .markdown-body blockquote {
      border-left: 4px solid rgb(var(--accent));
      padding-left: 1rem;
      color: var(--sub);
      font-style: italic;
      margin: 0.75rem 0;
    }
    
    /* Typing Indicator Animation */
    .dot-flashing {
      position: relative;
      width: 6px;
      height: 6px;
      border-radius: 5px;
      background-color: var(--sub);
      animation: dot-flashing 1s infinite linear alternate;
      animation-delay: .5s;
    }
    .dot-flashing::before, .dot-flashing::after {
      content: '';
      display: inline-block;
      position: absolute;
      top: 0;
      width: 6px;
      height: 6px;
      border-radius: 5px;
      background-color: var(--sub);
      animation: dot-flashing 1s infinite linear alternate;
    }
    .dot-flashing::before {
      left: -12px;
      animation-delay: 0s;
    }
    .dot-flashing::after {
      left: 12px;
      animation-delay: 1s;
    }
    @keyframes dot-flashing {
      0% { background-color: var(--sub); }
      50%, 100% { background-color: var(--border); }
    }
  </style>
</head>
<body x-data="chatApp" x-init="init()" class="h-full">

  <div class="h-full flex overflow-hidden">
    
    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
           class="fixed inset-y-0 left-0 z-30 w-72 flex flex-col glass-card border-r border-slate-200 dark:border-slate-800 transition-transform duration-300 ease-in-out md:static md:translate-x-0 flex-shrink-0"
           style="background-color: var(--side)">
      
      <!-- Brand Section -->
      <div class="p-5 flex items-center gap-3 border-b border-slate-200 dark:border-slate-800">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center btn-hover shadow-md shadow-indigo-500/10"
             style="background: rgba(var(--accent), 0.15); border: 1px solid rgba(var(--accent), 0.3); color: rgb(var(--accent))">
          <i data-lucide="terminal" class="w-5 h-5"></i>
        </div>
        <div>
          <h1 class="font-bold text-sm leading-tight tracking-wide">OpenCode Chat</h1>
          <p class="text-xs font-semibold leading-none tracking-wider mt-1" style="color: var(--sub)">LARAVEL INTEGRATION</p>
        </div>
      </div>

      <!-- Active Model Selection -->
      <div class="p-5 border-b border-slate-200 dark:border-slate-800">
        <label for="model" class="block text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--faint)">Model Aktif</label>
        <div class="relative">
          <select id="model" x-model="currentModelId" @change="selectModel($event.target.value)" :disabled="loadingModels"
                  class="w-full px-3 py-2 text-sm font-semibold rounded-lg appearance-none cursor-pointer glass-card text-inherit focus:outline-none transition-colors"
                  style="border-color: var(--border); background-color: var(--surface)">
            <template x-for="model in models" :key="model.id">
              <option :value="model.id" x-text="model.name" class="bg-inherit text-inherit"></option>
            </template>
          </select>
          <div class="absolute right-3 top-3 pointer-events-none" style="color: var(--sub)">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
          </div>
        </div>
      </div>

      <!-- Agent Selection -->
      <div class="px-5 pt-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <label for="agent-select" class="block text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--faint)">
          <span class="flex items-center gap-1.5">
            <i data-lucide="bot" class="w-3 h-3"></i>
            Agent
          </span>
        </label>
        <div class="relative">
          <select id="agent-select" x-model="currentAgentId" @change="selectAgent($event.target.value)" :disabled="loadingAgents"
                  class="w-full px-3 py-2 text-sm font-semibold rounded-lg appearance-none cursor-pointer glass-card text-inherit focus:outline-none transition-colors"
                  style="border-color: var(--border); background-color: var(--surface)">
            <option value="" disabled>Pilih Agent...</option>
            <template x-for="agent in agents" :key="agent.id">
              <option :value="agent.id" x-text="agent.name" class="bg-inherit text-inherit"></option>
            </template>
          </select>
          <div class="absolute right-3 top-3 pointer-events-none" style="color: var(--sub)">
            <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
          </div>
        </div>
        <p class="text-[10px] mt-1 font-medium" style="color: var(--faint)" x-show="loadingAgents">Memuat agent...</p>
      </div>

      <!-- Provider Selection -->
      <div class="px-5 pt-3 pb-4 border-b border-slate-200 dark:border-slate-800">
        <label for="provider-select" class="block text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--faint)">
          <span class="flex items-center gap-1.5">
            <i data-lucide="cloud" class="w-3 h-3"></i>
            Provider
          </span>
        </label>
        <div class="relative">
          <select id="provider-select" x-model="currentProviderId" @change="selectProvider($event.target.value)" :disabled="loadingProviders"
                  class="w-full px-3 py-2 text-sm font-semibold rounded-lg appearance-none cursor-pointer glass-card text-inherit focus:outline-none transition-colors"
                  style="border-color: var(--border); background-color: var(--surface)">
            <option value="" disabled>Pilih Provider...</option>
            <template x-for="provider in providers" :key="provider.id">
              <option :value="provider.id" x-text="provider.name" class="bg-inherit text-inherit"></option>
            </template>
          </select>
          <div class="absolute right-3 top-3 pointer-events-none" style="color: var(--sub)">
            <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
          </div>
        </div>
        <p class="text-[10px] mt-1 font-medium" style="color: var(--faint)" x-show="loadingProviders">Memuat provider...</p>
      </div>

      <!-- New Conversation Button -->
      <div class="p-4">
        <button @click="createNewSession()" :disabled="isLoading"
                class="w-full h-11 flex items-center justify-center gap-2 text-sm font-bold text-white rounded-lg btn-hover transition-all active:scale-[0.98] shadow-md shadow-indigo-500/10"
                style="background: linear-gradient(135deg, rgb(var(--accent)), rgb(var(--accent-hover)))">
          <i data-lucide="plus" class="w-4 h-4"></i>
          Percakapan baru
        </button>
      </div>

      <!-- Sessions History -->
      <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1">
        <div class="flex items-center justify-between px-3 mb-2 text-xs font-bold uppercase tracking-wider" style="color: var(--faint)">
          <span>Riwayat</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" style="background-color: var(--muted); color: var(--sub)" x-text="sessions.length"></span>
        </div>

        <template x-if="!sessions.length">
          <p class="text-xs text-center py-6" style="color: var(--faint)">Belum ada percakapan.</p>
        </template>

        <template x-for="session in sessions" :key="session.id">
          <div class="group relative flex items-center justify-between rounded-lg cursor-pointer px-3 py-2.5 transition-all"
               :style="session.id === currentSessionId ? 'background-color: var(--muted); color: var(--text)' : 'color: var(--sub)'"
               @click="selectSession(session.id)"
               :class="session.id === currentSessionId ? '' : 'hover:bg-slate-200/50 dark:hover:bg-slate-800/50 hover:text-inherit'">
            <div class="flex items-center gap-2.5 min-w-0 flex-1">
              <i data-lucide="message-square-code" class="w-4 h-4 flex-shrink-0" :style="session.id === currentSessionId ? 'color: rgb(var(--accent))' : ''"></i>
              <span class="text-sm font-semibold truncate leading-normal" x-text="session.title"></span>
            </div>
            
            <!-- Delete Button -->
            <button @click.stop="deleteSession(session.id)"
                    class="opacity-0 group-hover:opacity-100 p-1 rounded-md transition-all hover:bg-red-500/15 hover:text-red-500"
                    title="Hapus percakapan">
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </div>
        </template>
      </nav>

      <!-- Sidebar Status Footer -->
      <footer class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs font-semibold"
              style="color: var(--sub); background-color: rgba(var(--accent), 0.02)">
        <span>OpenCode API</span>
        <div class="flex items-center gap-1.5">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                  :style="isOnline ? 'background-color: rgb(var(--ok))' : 'background-color: rgb(var(--err))'"></span>
            <span class="relative inline-flex rounded-full h-2 w-2"
                  :style="isOnline ? 'background-color: rgb(var(--ok))' : 'background-color: rgb(var(--err))'"></span>
          </span>
          <span x-text="isOnline ? 'Terhubung' : 'Tidak tersedia'"></span>
        </div>
      </footer>
    </aside>

    <!-- Overlay on Mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
         class="fixed inset-0 z-20 bg-black/50 backdrop-blur-sm md:hidden transition-opacity"></div>

    <!-- Main Panel -->
    <main class="flex-1 flex flex-col overflow-hidden min-w-0">
      
      <!-- Top Header -->
      <header class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800 flex-shrink-0"
              style="background-color: var(--side)">
        <div class="flex items-center gap-4 min-w-0">
          <button @click="sidebarOpen = !sidebarOpen"
                  class="p-2 rounded-lg md:hidden glass-card btn-hover flex-shrink-0" style="border-color: var(--border)">
            <i data-lucide="menu" class="w-4 h-4"></i>
          </button>
          <div class="flex items-center gap-2 min-w-0">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                 style="background: rgba(var(--accent), 0.1); color: rgb(var(--accent))">
              <i data-lucide="sparkles" class="w-4 h-4"></i>
            </div>
            <div class="min-w-0">
              <h2 class="text-sm font-bold truncate" x-text="currentSession.id ? currentSession.title : activeModelName"></h2>
              <p class="text-[10px] font-semibold mt-0.5 tracking-wide uppercase" :style="isOnline ? 'color: rgb(var(--ok))' : 'color: var(--faint)'"
                 x-text="isOnline ? 'Siap menerima pertanyaan' : 'Memeriksa koneksi API...'"></p>
            </div>
          </div>
        </div>

        <!-- Theme Toggle Button -->
        <button @click="toggleTheme()" class="p-2 rounded-lg glass-card btn-hover transition-colors"
                style="border-color: var(--border); color: var(--sub)"
                :title="theme === 'dark' ? 'Gunakan tema terang' : 'Gunakan tema gelap'">
          <i data-lucide="sun" class="w-4 h-4" x-show="theme === 'dark'"></i>
          <i data-lucide="moon" class="w-4 h-4" x-show="theme === 'light'"></i>
        </button>
      </header>

      <!-- Scrollable Message Area -->
      <section class="flex-1 overflow-y-auto" id="message-container">
        
        <!-- Welcome Screen (Shown if session is empty) -->
        <template x-if="!currentSession.id || !currentSession.messages.length">
          <div class="max-w-3xl mx-auto px-6 py-12 flex flex-col justify-center min-h-full">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-indigo-500/10"
                 style="background: rgba(var(--accent), 0.15); color: rgb(var(--accent)); border: 1px solid rgba(var(--accent), 0.3)">
              <i data-lucide="sparkles" class="w-6 h-6"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-extrabold leading-tight tracking-tight">
              Mulai percakapan yang fokus dan rapi.
            </h2>
            <p class="text-sm md:text-base mt-3 leading-relaxed" style="color: var(--sub)">
              Pilih model di sidebar, tulis pertanyaan Anda, dan OpenCode akan membantu memformulasikan ide, memperbaiki kode, serta menjawab tantangan pemrograman Anda.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
              <button @click="includeDbContext = true; sendMessage('Apa isi data mahasiswa yang ada di tabel students? Tampilkan data yang tersedia.')"
                      class="flex flex-col text-left p-4 rounded-xl glass-card btn-hover group text-inherit active:scale-[0.99]"
                      style="border-color: var(--border); background-color: var(--surface)">
                <div class="p-2 rounded-lg mb-4 w-fit transition-colors group-hover:bg-indigo-500/10"
                     style="background-color: var(--muted); color: rgb(var(--accent))">
                  <i data-lucide="database" class="w-4 h-4"></i>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--text)">Cari Dokumen DB (MCP)</h3>
                <p class="text-xs leading-normal" style="color: var(--sub)">Ambil & ringkas data dari tabel documents via MCP.</p>
              </button>

              <button @click="sendMessage('Buatkan fungsi Fibonacci di PHP dengan memoization.')"
                      class="flex flex-col text-left p-4 rounded-xl glass-card btn-hover group text-inherit active:scale-[0.99]"
                      style="border-color: var(--border); background-color: var(--surface)">
                <div class="p-2 rounded-lg mb-4 w-fit transition-colors group-hover:bg-indigo-500/10"
                     style="background-color: var(--muted); color: rgb(var(--accent))">
                  <i data-lucide="code-2" class="w-4 h-4"></i>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--text)">Fibonacci PHP</h3>
                <p class="text-xs leading-normal" style="color: var(--sub)">Buat fungsi yang rapi dengan memoization.</p>
              </button>

              <button @click="sendMessage('Jelaskan relasi one-to-many pada Eloquent dengan contoh singkat.')"
                      class="flex flex-col text-left p-4 rounded-xl glass-card btn-hover group text-inherit active:scale-[0.99]"
                      style="border-color: var(--border); background-color: var(--surface)">
                <div class="p-2 rounded-lg mb-4 w-fit transition-colors group-hover:bg-indigo-500/10"
                     style="background-color: var(--muted); color: rgb(var(--accent))">
                  <i data-lucide="layers-3" class="w-4 h-4"></i>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--text)">Eloquent ORM</h3>
                <p class="text-xs leading-normal" style="color: var(--sub)">Pahami relasi data dengan contoh praktis.</p>
              </button>
            </div>
          </div>
        </template>

        <!-- Message List -->
        <template x-if="currentSession.id && currentSession.messages.length">
          <div class="max-w-4xl mx-auto px-6 py-8 space-y-6">
            <template x-for="(message, index) in currentSession.messages" :key="index">
              <article class="flex items-start gap-4" :class="message.role === 'user' ? 'justify-end' : ''">
                
                <!-- Bot Avatar -->
                <template x-if="message.role !== 'user'">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                       style="background: rgba(var(--accent), 0.1); color: rgb(var(--accent)); border: 1px solid rgba(var(--accent), 0.2)">
                    <i data-lucide="bot" class="w-4 h-4"></i>
                  </div>
                </template>

                <!-- Message Bubble -->
                <div class="flex flex-col max-w-[85%]">
                  <div class="flex items-center gap-2 mb-1 px-1" :class="message.role === 'user' ? 'justify-end' : ''">
                    <span class="text-xs font-bold" style="color: var(--sub)" x-text="message.role === 'user' ? 'Anda' : activeModelName"></span>
                    <time class="text-[10px]" style="color: var(--faint)" x-text="formatTime(message.timestamp)"></time>
                  </div>
                  
                  <div class="bubble markdown-body px-4 py-3 text-sm leading-relaxed rounded-2xl border"
                       :style="message.role === 'user' 
                          ? 'background-color: var(--user-bubble); border-color: rgba(var(--accent), 0.15); border-bottom-right-radius: 4px;' 
                          : 'background-color: var(--surface); border-color: var(--border); border-bottom-left-radius: 4px;'"
                       x-html="renderMarkdown(message.content)">
                  </div>
                </div>

                <!-- User Avatar -->
                <template x-if="message.role === 'user'">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                       style="background: rgba(var(--accent), 0.15); color: rgb(var(--accent)); border: 1px solid rgba(var(--accent), 0.25)">
                    <i data-lucide="user" class="w-4 h-4"></i>
                  </div>
                </template>

              </article>
            </template>

            <!-- Loading response from assistant -->
            <template x-if="isLoading && currentSession.messages.at(-1)?.role === 'user'">
              <article class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background: rgba(var(--accent), 0.1); color: rgb(var(--accent)); border: 1px solid rgba(var(--accent), 0.2)">
                  <i data-lucide="bot" class="w-4 h-4"></i>
                </div>
                <div class="flex flex-col">
                  <div class="flex items-center gap-2 mb-1 px-1">
                    <span class="text-xs font-bold" style="color: var(--sub)" x-text="activeModelName"></span>
                  </div>
                  <div class="bubble px-6 py-4 rounded-2xl border flex items-center justify-center h-10 w-16"
                       style="background-color: var(--surface); border-color: var(--border); border-bottom-left-radius: 4px;">
                    <div class="dot-flashing"></div>
                  </div>
                </div>
              </article>
            </template>
          </div>
        </template>
      </section>

      <!-- Composer Input Area -->
      <footer class="p-6 border-t border-slate-200 dark:border-slate-800 flex-shrink-0"
              style="background-color: var(--side)">
        <div class="max-w-4xl mx-auto">
          
          <!-- Local Error Message Banner -->
          <template x-if="errorMessage">
            <div class="mb-4 px-4 py-2.5 rounded-lg border text-xs font-medium flex items-center gap-2"
                 style="background-color: rgba(var(--err), 0.08); border-color: rgba(var(--err), 0.2); color: rgb(var(--err))">
              <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
              <span x-text="errorMessage"></span>
            </div>
          </template>

          <!-- MCP DB Context Toggle -->
          <div class="flex items-center justify-between mb-2 px-1 text-xs select-none">
            <label class="flex items-center gap-2 cursor-pointer font-semibold transition-colors"
                   :class="includeDbContext ? 'text-indigo-400 font-bold' : ''" style="color: var(--sub)">
              <input type="checkbox" x-model="includeDbContext" class="rounded accent-indigo-600 cursor-pointer w-3.5 h-3.5">
              <i data-lucide="database" class="w-3.5 h-3.5"></i>
              <span>Hubungkan Konteks Database (MCP)</span>
            </label>
            <span class="text-[10px] font-medium" style="color: var(--faint)" x-show="includeDbContext">
              ⚡ Pencarian data MySQL otomatis aktif
            </span>
          </div>

          <!-- Input Box -->
          <div class="relative flex items-end rounded-xl border p-2 transition-all shadow-sm"
               style="border-color: var(--border); background-color: var(--surface)"
               :class="focusComposer ? 'ring-2 ring-indigo-500/20 border-indigo-500/50' : ''">
            <textarea rows="1" x-model="inputText" 
                      @focus="focusComposer = true" @blur="focusComposer = false"
                      @keydown.enter="if(!event.shiftKey) { event.preventDefault(); sendMessage(); }" :disabled="isLoading" 
                      placeholder="Tulis pesan untuk asisten..."
                      class="w-full bg-transparent border-0 px-3 py-2 text-sm leading-relaxed focus:outline-none resize-none max-h-40 min-h-[2.25rem]"></textarea>
            
            <button @click="sendMessage()" :disabled="isLoading || !inputText.trim()"
                    class="w-9 h-9 rounded-lg flex items-center justify-center text-white transition-all btn-hover active:scale-95 disabled:scale-100 disabled:opacity-50 flex-shrink-0 ml-2 shadow-md shadow-indigo-500/10"
                    style="background: linear-gradient(135deg, rgb(var(--accent)), rgb(var(--accent-hover)))"
                    title="Kirim pesan">
              <i data-lucide="arrow-up" class="w-4 h-4"></i>
            </button>
          </div>
          <p class="text-[10px] mt-2 text-center font-medium" style="color: var(--faint)">
            Tekan <kbd class="px-1 py-0.5 rounded bg-slate-200/60 dark:bg-slate-800 text-[9px] font-bold">Enter</kbd> untuk mengirim, <kbd class="px-1 py-0.5 rounded bg-slate-200/60 dark:bg-slate-800 text-[9px] font-bold">Shift + Enter</kbd> untuk baris baru.
          </p>
        </div>
      </footer>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());

    document.addEventListener('alpine:init', () => Alpine.data('chatApp', () => ({
      sessions: [],
      currentSessionId: null,
      models: [],
      currentModelId: null,
      agents: [],
      currentAgentId: '',
      loadingAgents: true,
      providers: [],
      currentProviderId: '',
      loadingProviders: true,
      inputText: '',
      isLoading: false,
      isOnline: false,
      api: null,
      errorMessage: '',
      loadingModels: true,
      focusComposer: false,
      theme: localStorage.getItem('opencode-theme') || 'dark',
      sidebarOpen: false,
      includeDbContext: true,

      async init() {
        this.applyTheme();
        this.api = new OpenCodeApiClient('/api');
        await this.checkApiStatus();
        await Promise.all([
          this.loadModels(),
          this.loadAgents(),
          this.loadProviders(),
        ]);
        await this.loadSessions();
        
        this.$watch('sessions', () => this.afterRender());
        this.$watch('currentSessionId', () => this.afterRender());
      },

      applyTheme() {
        document.documentElement.dataset.theme = this.theme;
        if (this.theme === 'dark') {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }
        localStorage.setItem('opencode-theme', this.theme);
      },

      toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        this.applyTheme();
      },

      async checkApiStatus() {
        try {
          this.isOnline = (await this.api.checkStatus())?.online === true;
        } catch (e) {
          this.isOnline = false;
        }
      },

      normalize(raw) {
        if (Array.isArray(raw)) return raw;
        return [raw?.models, raw?.data, raw?.items, raw?.result, raw?.model, raw?.list].find(Array.isArray) || [];
      },

      async loadModels() {
        this.loadingModels = true;
        try {
          const rawModels = this.normalize(await this.api.listModels());
          // Hanya tampilkan model yang aktif (menghindari error model deprecated seperti hy3-free)
          const activeModels = rawModels.filter(m => typeof m === 'object' && m !== null && m.status === 'active');
          const modelsToUse = activeModels.length > 0 ? activeModels : rawModels;

          this.models = modelsToUse.map((m, i) => 
            typeof m === 'string' ? { id: m, name: m, providerID: 'opencode' } : { 
              id: String(m.id ?? m.model ?? `model_${i}`), 
              name: String(m.name ?? m.label ?? m.id ?? `Model ${i + 1}`),
              providerID: m.providerID ?? 'opencode'
            }
          );
          
          if (!this.models.length) throw Error();
          this.currentModelId = this.models[0].id;
        } catch (e) {
          this.models = [{ id: 'opencode-default', name: 'OpenCode Default' }];
          this.currentModelId = this.models[0].id;
          this.errorMessage = 'Model tidak dapat dimuat dari server OpenCode.';
        } finally {
          this.loadingModels = false;
        }
      },

      async loadSessions() {
        try {
          const r = await this.api.listSessions();
          const list = Array.isArray(r) ? r : (Array.isArray(r?.data) ? r.data : []);
          
          this.sessions = list.map(s => {
            let modelId = this.currentModelId;
            if (s.model) {
              modelId = typeof s.model === 'object' ? (s.model.id || s.model.model || modelId) : s.model;
            }
            return {
              id: s.id,
              model: modelId,
              title: s.title || 'Percakapan',
              messages: []
            };
          });

          if (this.sessions.length) {
            await this.selectSession(this.sessions[0].id);
          }
        } catch (e) {
          console.error('Failed to load sessions:', e);
          this.sessions = [];
        }
      },

      get currentSession() {
        return this.sessions.find(s => s.id === this.currentSessionId) || { 
          id: null, 
          title: '', 
          messages: [], 
          model: null 
        };
      },

      get activeModelName() {
        return this.models.find(m => m.id === this.currentModelId)?.name || 'OpenCode';
      },

      async createNewSession() {
        if (this.isLoading) return;
        this.isLoading = true;
        try {
          const activeModelObj = this.models.find(m => m.id === this.currentModelId);
          const modelPayload = activeModelObj ? { id: activeModelObj.id, providerID: activeModelObj.providerID } : { id: this.currentModelId, providerID: 'opencode' };
          const r = await this.api.createSession({ model: modelPayload });
          if (!r?.data?.id) throw Error('Server tidak mengembalikan ID sesi.');
          
          const s = { 
            id: r.data.id, 
            model: this.currentModelId, 
            title: 'Percakapan baru', 
            messages: [] 
          };
          this.sessions = [s, ...this.sessions];
          this.currentSessionId = s.id;
          this.errorMessage = '';
        } catch (e) {
          this.errorMessage = e.message || 'Gagal membuat sesi baru.';
        } finally {
          this.isLoading = false;
          this.sidebarOpen = false;
        }
      },

      async deleteSession(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus percakapan ini?')) {
          return;
        }
        
        try {
          await this.api.deleteSession(id);
          
          this.sessions = this.sessions.filter(s => s.id !== id);
          if (this.currentSessionId === id) {
            if (this.sessions.length) {
              await this.selectSession(this.sessions[0].id);
            } else {
              this.currentSessionId = null;
            }
          }
          this.errorMessage = '';
        } catch (e) {
          console.error(e);
          this.errorMessage = e.message || 'Gagal menghapus percakapan dari server.';
        }
      },

      async selectSession(id) {
        this.currentSessionId = id;
        this.sidebarOpen = false;
        this.errorMessage = '';

        const s = this.sessions.find(x => x.id === id);
        if (!s) return;

        // Validasi apakah model sesi ini didukung oleh server (masih aktif)
        const sessionModelId = s.model || this.currentModelId;
        const modelExists = this.models.some(m => m.id === sessionModelId);
        
        if (modelExists) {
          this.currentModelId = sessionModelId;
        } else if (this.models.length > 0) {
          // Model lama tidak aktif/deprecated (misal: hy3-free), dialihkan ke model aktif pertama
          const fallbackModel = this.models[0];
          this.currentModelId = fallbackModel.id;
          s.model = fallbackModel.id;
          
          // Tampilkan banner peringatan ramah ke user
          this.errorMessage = `Model sebelumnya '${sessionModelId}' tidak aktif lagi. Sesi ini otomatis dialihkan ke '${fallbackModel.name}'.`;
          
          // Perbarui model di server agar chat selanjutnya berfungsi
          try {
            await this.api.switchModel(s.id, { id: fallbackModel.id, providerID: fallbackModel.providerID });
          } catch (e) {
            console.warn('Gagal mengubah model sesi di server:', e);
          }
        }

        // Fetch messages if they are empty
        if (!s.messages || s.messages.length === 0) {
          this.isLoading = true;
          try {
            const response = await this.api.getSession(id);
            if (response && response.messages) {
              s.messages = response.messages;
            }
          } catch (e) {
            console.error('Failed to load session details:', e);
            this.errorMessage = 'Gagal memuat detail percakapan dari server.';
          } finally {
            this.isLoading = false;
            this.scroll();
          }
        } else {
          this.scroll();
        }
      },

      async selectModel(id) {
        this.currentModelId = id;
        const s = this.currentSession;
        if (!s || !s.id || !s.messages.length) return;
        
        const activeModelObj = this.models.find(m => m.id === id);
        const modelPayload = activeModelObj ? { id: activeModelObj.id, providerID: activeModelObj.providerID } : { id: id, providerID: 'opencode' };
        
        try {
          await this.api.switchModel(s.id, modelPayload);
        } catch (e) {
          console.warn(e);
        }
        this.sessions = this.sessions.map(x => x.id === s.id ? { ...x, model: id } : x);
      },

      async loadAgents() {
        this.loadingAgents = true;
        try {
          const rawAgents = this.normalize(await this.api.listAgents());
          this.agents = rawAgents.map((a, i) =>
            typeof a === 'string' ? { id: a, name: a } : {
              id: String(a.id ?? a.agentId ?? `agent_${i}`),
              name: String(a.name ?? a.label ?? a.id ?? `Agent ${i + 1}`),
            }
          );
          if (this.agents.length > 0) {
            this.currentAgentId = this.agents.find(agent => agent.id === 'build')?.id
              ?? this.agents[0].id;
          }
        } catch (e) {
          console.warn('Failed to load agents:', e);
          this.agents = [{ id: 'default', name: 'Default Agent' }];
          this.currentAgentId = 'default';
        } finally {
          this.loadingAgents = false;
        }
      },

      async loadProviders() {
        this.loadingProviders = true;
        try {
          const rawProviders = this.normalize(await this.api.listProviders());
          this.providers = rawProviders.map((p, i) =>
            typeof p === 'string' ? { id: p, name: p } : {
              id: String(p.id ?? p.providerId ?? `provider_${i}`),
              name: String(p.name ?? p.label ?? p.id ?? `Provider ${i + 1}`),
            }
          );
          if (this.providers.length > 0) {
            this.currentProviderId = this.providers[0].id;
          }
        } catch (e) {
          console.warn('Failed to load providers:', e);
          this.providers = [{ id: 'opencode', name: 'OpenCode' }];
          this.currentProviderId = 'opencode';
        } finally {
          this.loadingProviders = false;
        }
      },

      async selectAgent(id) {
        this.currentAgentId = id;
        const s = this.currentSession;
        if (!s || !s.id) return;
        try {
          await this.api.switchAgent(s.id, id);
        } catch (e) {
          console.warn('Failed to switch agent:', e);
        }
      },

      async selectProvider(id) {
        this.currentProviderId = id;
        // Provider selection is stored locally and sent with messages
      },

      async sendMessage(preset = null) {
        const text = (preset ?? this.inputText).trim();
        if (!text || this.isLoading) return;
        
        if (preset === null) this.inputText = '';
        
        if (!this.currentSessionId) {
          await this.createNewSession();
        }
        
        const s = this.currentSession;
        if (!s || !s.id) return;
        
        const id = s.id;
        
        // Cek jika ini pesan pertama untuk menentukan judul riwayat (seperti ChatGPT)
        const isFirstMessage = s.messages.length === 0;
        let title = s.title;
        if (isFirstMessage) {
          // Bersihkan whitespace/newline berlebih dan pisahkan per kata
          const cleanText = text.replace(/[\r\n\t]+/g, ' ').trim();
          const words = cleanText.split(/\s+/);
          if (words.length <= 5) {
            title = cleanText;
          } else {
            title = words.slice(0, 5).join(' ') + '...';
          }
          // Batasi maksimal karakter agar tampilan sidebar rapi
          if (title.length > 30) {
            title = title.slice(0, 27) + '...';
          }
        }
        
        // Append user message local copy
        this.sessions = this.sessions.map(x => x.id === id ? { 
          ...x, 
          title, 
          messages: [...x.messages, { 
            role: 'user', 
            content: text, 
            timestamp: new Date().toISOString() 
          }] 
        } : x);
        
        this.errorMessage = '';
        this.isLoading = true;
        this.scroll();

        try {
          const r = await this.api.sendMessage(id, text, {
            agent: this.currentAgentId || undefined,
            provider: this.currentProviderId || undefined,
            include_db_context: this.includeDbContext,
          });
          const content = typeof r === 'string' ? r : r?.content;
          
          if (!content) throw Error('OpenCode tidak mengembalikan isi respons.');
          
          // Append assistant message local copy
          this.sessions = this.sessions.map(x => x.id === id ? { 
            ...x, 
            messages: [...x.messages, { 
              role: 'assistant', 
              content, 
              timestamp: new Date().toISOString() 
            }] 
          } : x);

          // Jika ini pesan pertama, sinkronisasikan nama judul baru ke server/database
          if (isFirstMessage) {
            try {
              await this.api.renameSession(id, title);
            } catch (renameError) {
              console.warn('Gagal mengubah judul sesi di server:', renameError);
            }
          }
        } catch (e) {
          this.errorMessage = e.message || 'Pesan tidak dapat diproses.';
        } finally {
          this.isLoading = false;
          this.scroll();
        }
      },

      formatTime(t) {
        try {
          return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(t));
        } catch (e) {
          return '';
        }
      },

      renderMarkdown(c) {
        try {
          return marked.parse(c || '');
        } catch (e) {
          return c || '';
        }
      },

      afterRender() {
        this.$nextTick(() => {
          window.lucide?.createIcons();
          document.querySelectorAll('.bubble pre code').forEach(x => {
            if (!x.dataset.highlighted) {
              hljs.highlightElement(x);
              x.dataset.highlighted = 'true';
            }
          });
          this.scroll();
        });
      },

      scroll() {
        this.$nextTick(() => {
          const e = document.getElementById('message-container');
          if (e) e.scrollTop = e.scrollHeight;
        });
      }
    })));
  </script>
</body>
</html>
