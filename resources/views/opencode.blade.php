<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OpenCode AI Studio • MySQL MCP Assistant</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- Markdown Parser -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>

<body>

    <!-- Header Navigation -->
    <header class="app-header">
        <div class="brand-container">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M4 7V4h16v3M9 20h6M12 4v16" />
                </svg>
            </div>
            <div class="brand-info">
                <h1>OpenCode Studio <span style="font-size: 0.75rem; font-weight: 500; color: #818cf8; background: rgba(99,102,241,0.15); padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(99,102,241,0.3);">v1.18</span></h1>
                <p>Laravel Client • OpenCode Engine • MCP Protocol</p>
            </div>
        </div>

        <div class="header-meta">
            <!-- OpenCode Health Status -->
            @if(isset($health) && $health)
                <span class="badge badge-success">
                    <span class="pulse-dot"></span> OpenCode Online
                </span>
            @else
                <span class="badge badge-danger">
                    <span class="pulse-dot"></span> OpenCode Offline
                </span>
            @endif

            <!-- MCP MySQL Status -->
            @if(!empty($mcp['mysql']) && ($mcp['mysql']['status'] ?? '') === 'connected')
                <span class="badge badge-mcp">
                    <span class="pulse-dot"></span> MCP MySQL: Connected (mcp_demo_db)
                </span>
            @else
                <span class="badge" style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(255,255,255,0.06);">
                    ● MCP: Disconnected
                </span>
            @endif

            <!-- Link to API Dashboard -->
            <a href="{{ route('api.dashboard') }}" class="nav-btn">
                <span>API Dashboard</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 17l9.2-9.2M17 17V7H7"/>
                </svg>
            </a>
        </div>
    </header>

    <!-- Main Application Container -->
    <main class="app-container">

        <!-- Sidebar Navigation -->
        <aside class="sidebar-panel">

            <!-- Sessions Card -->
            <div class="card sidebar-card-sessions">
                <button class="btn-new-session" onclick="newSession()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Session Baru</span>
                </button>

                <div class="card-header">
                    <span class="card-title">Riwayat Sesi</span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">{{ count($sessions) }} sesi</span>
                </div>

                <div id="sessions" class="session-list">
                    @forelse($sessions as $session)
                        <div class="session-item" id="session-item-{{ $session['id'] }}" onclick="selectSession('{{ $session['id'] }}')">
                            <span class="session-title-text" title="{{ $session['title'] ?? $session['id'] }}">
                                {{ $session['title'] ?? $session['id'] }}
                            </span>
                            <button class="btn-delete-session" onclick="deleteSession(event, '{{ $session['id'] }}')" title="Hapus sesi">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    @empty
                        <div style="text-align: center; color: var(--text-muted); font-size: 0.82rem; padding: 20px 0;">
                            Belum ada sesi aktif.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- AI Engine Settings Card -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Konfigurasi Agen</span>
                </div>

                <div class="form-group">
                    <label for="agent" class="form-label">Active Agent</label>
                    <select id="agent" class="custom-select">
                        <option value="">Default AI Agent</option>
                        @foreach($agents as $agent)
                            @if(!($agent['hidden'] ?? false))
                                <option value="{{ $agent['name'] ?? $agent['id'] ?? '' }}"
                                    {{ ($agent['name'] ?? '') === 'database-assistant' ? 'selected' : '' }}>
                                    {{ $agent['name'] ?? $agent['id'] ?? 'Agent' }}
                                    @if(($agent['name'] ?? '') === 'database-assistant')
                                        ★ [MySQL MCP Students]
                                    @endif
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <div class="agent-helper">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <span>Gunakan <b>database-assistant</b> untuk membaca tabel students.</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="provider" class="form-label">Provider AI</label>
                    <select id="provider" class="custom-select">
                        @php
                            $connectedList = $providers['connected'] ?? ['opencode'];
                        @endphp
                        <optgroup label="● Terkoneksi &amp; Siap Pakai">
                            @foreach(($providers['all'] ?? []) as $provider)
                                @if(in_array($provider['id'] ?? '', $connectedList))
                                    <option value="{{ $provider['id'] ?? '' }}" selected>
                                        {{ $provider['name'] ?? $provider['id'] }} (Aktif / Default)
                                    </option>
                                @endif
                            @endforeach
                        </optgroup>
                        <optgroup label="○ Katalog Lainnya (Memerlukan API Key)">
                            @foreach(($providers['all'] ?? []) as $provider)
                                @if(!in_array($provider['id'] ?? '', $connectedList))
                                    <option value="{{ $provider['id'] ?? '' }}" disabled>
                                        {{ $provider['name'] ?? $provider['id'] }} (Belum login)
                                    </option>
                                @endif
                            @endforeach
                        </optgroup>
                    </select>
                    <div class="agent-helper" style="color: var(--text-muted); font-size: 0.72rem; margin-top: 4px;">
                        <span>Provider aktif saat ini: <b style="color: #38bdf8;">OpenCode Zen</b> (Free Tier).</span>
                    </div>
                </div>

                <!-- OpenRouter Model Selector (hanya tampil saat engine = Hermes) -->
                <div id="openrouter-model-group" class="form-group" style="display: none; margin-top: 4px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                    <label for="openrouter-model" class="form-label" style="color: #a5b4fc;">
                        🌐 Model OpenRouter
                    </label>
                    <select id="openrouter-model" class="custom-select"
                        style="border-color: rgba(99,102,241,0.4);"
                        onchange="updateModelHint(this.value)">
                        <optgroup label="✅ Support Tool Calling">
                            <option value="openai/gpt-4o-mini" selected>GPT-4o Mini (Recommended)</option>
                            <option value="openai/gpt-4o">GPT-4o (Lebih Pintar)</option>
                            <option value="anthropic/claude-3-haiku">Claude 3 Haiku (Cepat)</option>
                            <option value="mistralai/mistral-7b-instruct">Mistral 7B Instruct</option>
                        </optgroup>
                        <optgroup label="⚠️ Direct Reply saja (No Tool Calling)">
                            <option value="nousresearch/hermes-3-llama-3.1-405b">Hermes 3 Llama 405B</option>
                            <option value="meta-llama/llama-3.1-8b-instruct">Llama 3.1 8B Instruct</option>
                        </optgroup>
                    </select>
                    <div class="agent-helper" style="color: var(--text-muted); font-size: 0.70rem; margin-top: 4px;">
                        <span id="model-hint-text">🔧 Tool Calling aktif · Bisa baca DB</span>
                    </div>
                </div>
            </div>

        </aside>

        <!-- Main Chat Area -->
        <section class="chat-panel">

            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-header-title">
                    <div class="avatar bot-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <div>
                        <h2 id="currentSessionTitle">Chat Assistant</h2>
                        <span id="currentSessionId" style="font-size: 0.73rem; color: var(--text-muted); font-family: var(--font-mono);">
                            Pilih sesi atau mulai sesi baru
                        </span>
                    </div>
                </div>

                <div style="display: flex; gap: 8px;">
                    <span style="font-size: 0.75rem; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                        Database: <b>mcp_demo_db</b> (XAMPP)
                    </span>
                </div>
            </div>

            <!-- Messages Container -->
            <div id="chat" class="chat-messages">
                <!-- Welcome Hero State -->
                <div class="welcome-hero" id="welcomeHero">
                    <div class="welcome-badge-icon">
                        <svg viewBox="0 0 24 24">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                        </svg>
                    </div>
                    <h3>Asisten Database Student (MySQL via MCP)</h3>
                    <p>
                        Chatbot ini terintegrasi langsung dengan database <b>mcp_demo_db</b> di XAMPP melalui <b>Model Context Protocol (MCP)</b>. OpenCode dapat membaca data nyata dari tabel <b>students</b> secara real-time.
                    </p>

                    <div class="prompt-grid">
                        <div class="prompt-card" onclick="setPromptAndSend('Tampilkan semua data student yang ada di database.')">
                            <div class="prompt-card-header">
                                <span>📦</span>
                                <span>Semua Student</span>
                            </div>
                            <div class="prompt-card-desc">Tampilkan seluruh baris dari tabel students.</div>
                        </div>

                        <div class="prompt-card" onclick="setPromptAndSend('Berapa jumlah student yang ada di database?')">
                            <div class="prompt-card-header">
                                <span>💻</span>
                                <span>Jumlah Student</span>
                            </div>
                            <div class="prompt-card-desc">Hitung jumlah data student dari database.</div>
                        </div>

                        <div class="prompt-card" onclick="setPromptAndSend('Tampilkan nama semua student.')">
                            <div class="prompt-card-header">
                                <span>💰</span>
                                <span>Nama Student</span>
                            </div>
                            <div class="prompt-card-desc">Ambil kolom name dari tabel students.</div>
                        </div>

                        <div class="prompt-card" onclick="setPromptAndSend('Jelaskan struktur tabel students.')">
                            <div class="prompt-card-header">
                                <span>🎧</span>
                                <span>Struktur Tabel</span>
                            </div>
                            <div class="prompt-card-desc">Periksa kolom dan tipe data tabel students.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Bar -->
            <div class="chat-input-area">

                <!-- A/B Testing: AI Engine Selector -->
                <div style="display: flex; align-items: center; gap: 10px; padding: 8px 4px 6px; border-bottom: 1px solid var(--border-color); margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap;">
                        🔬 <b style="color: #a5b4fc;">A/B Test Engine:</b>
                    </span>
                    <select id="agent-selector" style="
                        flex: 1;
                        background: rgba(99,102,241,0.1);
                        border: 1px solid rgba(99,102,241,0.35);
                        color: #e2e8f0;
                        border-radius: 8px;
                        padding: 5px 10px;
                        font-size: 0.78rem;
                        font-family: var(--font-mono);
                        cursor: pointer;
                        outline: none;
                        transition: border-color 0.2s;
                    " onchange="onEngineChange(this)">
                        <option value="opencode">⚙️ OpenCode (Local · MCP)</option>
                        <option value="hermes">🌐 Hermes 3 Llama (OpenRouter · Cloud)</option>
                    </select>
                    <span id="engine-badge" style="
                        font-size: 0.68rem;
                        padding: 3px 8px;
                        border-radius: 20px;
                        background: rgba(16,185,129,0.15);
                        color: #34d399;
                        border: 1px solid rgba(16,185,129,0.3);
                        white-space: nowrap;
                    ">● Local</span>
                </div>

                <!-- Model Selector Row — muncul saat Hermes dipilih -->
                <div id="inline-model-row" style="
                    display: none;
                    align-items: center;
                    gap: 8px;
                    padding: 6px 4px 8px;
                    border-bottom: 1px solid var(--border-color);
                    margin-bottom: 6px;
                ">
                    <span style="font-size: 0.72rem; color: #a5b4fc; white-space: nowrap;">
                        🌐 <b>Model:</b>
                    </span>
                    <select id="openrouter-model-inline" style="
                        flex: 1;
                        background: rgba(99,102,241,0.12);
                        border: 1px solid rgba(99,102,241,0.4);
                        color: #e2e8f0;
                        border-radius: 8px;
                        padding: 4px 10px;
                        font-size: 0.76rem;
                        font-family: var(--font-mono);
                        cursor: pointer;
                        outline: none;
                    " onchange="updateModelHint(this.value)">
                        <optgroup label="✅ Support Tool Calling">
                            <option value="openai/gpt-4o-mini" selected>GPT-4o Mini ⚡ (Recommended)</option>
                            <option value="openai/gpt-4o">GPT-4o 🧠 (Lebih Pintar)</option>
                            <option value="anthropic/claude-3-haiku">Claude 3 Haiku 🚀 (Cepat)</option>
                            <option value="mistralai/mistral-7b-instruct">Mistral 7B Instruct</option>
                        </optgroup>
                        <optgroup label="⚠️ Direct Reply saja (No Tool Calling)">
                            <option value="nousresearch/hermes-3-llama-3.1-405b">Hermes 3 Llama 405B</option>
                            <option value="meta-llama/llama-3.1-8b-instruct">Llama 3.1 8B Instruct</option>
                        </optgroup>
                    </select>
                    <span id="tool-calling-badge" style="
                        font-size: 0.65rem;
                        padding: 2px 7px;
                        border-radius: 20px;
                        background: rgba(16,185,129,0.12);
                        color: #34d399;
                        border: 1px solid rgba(16,185,129,0.25);
                        white-space: nowrap;
                    ">🔧 Tool</span>
                </div>

                <!-- Quick Chips -->
                <div class="quick-chips-bar">
                    <span style="font-size: 0.75rem; color: var(--text-muted); padding-right: 4px;">Saran:</span>
                    <button type="button" class="chip-btn" onclick="setPrompt('Tampilkan semua data student yang ada di database.')">
                        <span>📋</span> Semua Student
                    </button>
                    <button type="button" class="chip-btn" onclick="setPrompt('Berapa jumlah student yang ada di database?')">
                        <span>🔢</span> Jumlah Student
                    </button>
                    <button type="button" class="chip-btn" onclick="setPrompt('Tampilkan nama semua student.')">
                        <span>👤</span> Nama Student
                    </button>
                    <button type="button" class="chip-btn" onclick="setPrompt('Jelaskan struktur tabel students.')">
                        <span>🧾</span> Struktur Tabel
                    </button>
                </div>

                <!-- Input Capsule -->
                <div class="input-wrapper">
                    <textarea 
                        id="message" 
                        class="chat-textarea" 
                        rows="1" 
                        placeholder="Ketik pertanyaan untuk asisten database (contoh: Tampilkan semua student)..."
                        onkeydown="handleKeyDown(event)"></textarea>

                    <button id="sendBtn" class="send-btn" onclick="sendMessage()" title="Kirim Pesan">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>

                <div class="input-hint">
                    <span>Tekan <b>Enter</b> untuk mengirim, <b>Shift + Enter</b> untuk baris baru</span>
                    <span id="engine-hint-text">Protokol: <b>Model Context Protocol (stdio)</b></span>
                </div>
            </div>

        </section>

    </main>

    <script>
        let currentSession = null;
        const initialSessions = @json($sessions);

        // Auto select first session if available
        document.addEventListener("DOMContentLoaded", () => {
            if (initialSessions && initialSessions.length > 0) {
                selectSession(initialSessions[0].id, initialSessions[0].title);
            }
        });

        /* =========================
           CREATE SESSION
        ========================= */
        async function newSession() {
            try {
                const response = await fetch("{{ route('opencode.session.create') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        title: "Sesi Student " + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    })
                });

                const data = await response.json();
                if (!data || !data.id) {
                    throw new Error("Gagal membuat sesi baru di OpenCode.");
                }

                currentSession = data.id;
                location.reload();
            } catch (error) {
                alert(error.message || "Terjadi kesalahan saat membuat sesi.");
            }
        }

        async function selectSession(id, title = null) {
            currentSession = id;

            // Highlight active session in sidebar
            document.querySelectorAll('.session-item').forEach(el => el.classList.remove('active'));
            const activeEl = document.getElementById(`session-item-${id}`);
            if (activeEl) activeEl.classList.add('active');

            // Update Header
            document.getElementById("currentSessionId").textContent = `ID: ${id}`;
            if (title) {
                document.getElementById("currentSessionTitle").textContent = title;
            }

            const chat = document.getElementById("chat");
            chat.innerHTML = `
                <div class="message-row assistant">
                    <div class="avatar bot-avatar">AI</div>
                    <div class="message-content-wrapper">
                        <span class="message-sender-name">OpenCode Assistant</span>
                        <div class="message-bubble">
                            <div class="typing-dots">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            try {
                const response = await fetch(`/session/${id}/messages`);
                const messages = await response.json();

                if (!messages || messages.length === 0) {
                    // Show welcome hero inside chat
                    chat.innerHTML = "";
                    const hero = document.getElementById("welcomeHero");
                    if (hero) chat.appendChild(hero.cloneNode(true));
                    return;
                }

                renderMessages(messages);
            } catch (err) {
                chat.innerHTML = `
                    <div class="message-row assistant">
                        <div class="avatar bot-avatar">!</div>
                        <div class="message-content-wrapper">
                            <span class="message-sender-name">Sistem</span>
                            <div class="message-bubble" style="color: var(--accent-rose);">
                                Gagal memuat percakapan dari server OpenCode.
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        /* =========================
           DELETE SESSION
        ========================= */
        async function deleteSession(event, id) {
            event.stopPropagation();
            if (!confirm("Hapus sesi ini dari riwayat?")) return;

            try {
                await fetch(`/session/${id}`, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                location.reload();
            } catch (err) {
                alert("Gagal menghapus sesi.");
            }
        }

        /* =========================
           ENGINE SELECTOR HANDLER
        ========================= */
        function onEngineChange(select) {
            const badge          = document.getElementById('engine-badge');
            const hint           = document.getElementById('engine-hint-text');
            const modelGroup     = document.getElementById('openrouter-model-group');
            const modelSelect    = document.getElementById('openrouter-model');

            if (select.value === 'hermes') {
                // Tampilkan badge & hint untuk Cloud
                badge.style.background  = 'rgba(99,102,241,0.15)';
                badge.style.color       = '#818cf8';
                badge.style.borderColor = 'rgba(99,102,241,0.3)';
                badge.textContent       = '● Cloud (OpenRouter)';

                // Tampilkan model selector OpenRouter
                modelGroup.style.display = 'block';

                // Update hint sesuai model yang dipilih
                updateModelHint(modelSelect.value);
            } else {
                badge.style.background  = 'rgba(16,185,129,0.15)';
                badge.style.color       = '#34d399';
                badge.style.borderColor = 'rgba(16,185,129,0.3)';
                badge.textContent       = '● Local';
                hint.innerHTML          = 'Protokol: <b>Model Context Protocol (stdio)</b>';

                // Sembunyikan model selector OpenRouter
                modelGroup.style.display = 'none';
            }
        }

        // Update hint di bawah model selector berdasarkan model yang dipilih
        function updateModelHint(modelValue) {
            const hint      = document.getElementById('engine-hint-text');
            const modelHint = document.getElementById('model-hint-text');

            const toolCallingModels = [
                'openai/gpt-4o-mini',
                'openai/gpt-4o',
                'anthropic/claude-3-haiku',
                'mistralai/mistral-7b-instruct',
            ];

            if (toolCallingModels.includes(modelValue)) {
                hint.innerHTML      = `Model: <b>${modelValue}</b> · Tool Calling aktif`;
                modelHint.textContent = '🔧 Tool Calling aktif · Bisa baca DB';
                modelHint.style.color = '#34d399';
            } else {
                hint.innerHTML      = `Model: <b>${modelValue}</b> · Direct Reply saja`;
                modelHint.textContent = '⚠️ Model ini tidak support Tool Calling';
                modelHint.style.color = '#f59e0b';
            }
        }

        /* =========================
           SEND MESSAGE
        ========================= */
        async function sendMessage() {
            const selectedEngine = document.getElementById('agent-selector').value;

            const textarea = document.getElementById("message");
            const message = textarea.value.trim();
            if (!message) return;

            // Remove welcome hero if present
            const hero = document.querySelector("#chat .welcome-hero");
            if (hero) hero.remove();

            // Append user bubble
            appendMessageBubble(message, "user");
            textarea.value = "";
            textarea.style.height = 'auto';

            // Show typing indicator
            const typingId = "typing-" + Date.now();
            const chat = document.getElementById("chat");
            const typingRow = document.createElement("div");
            typingRow.id = typingId;
            typingRow.className = "message-row assistant";
            const engineLabel = selectedEngine === 'hermes'
                ? 'Hermes 3 (OpenRouter · Cloud)'
                : 'database-assistant (MCP · Local)';
            typingRow.innerHTML = `
                <div class="avatar bot-avatar" style="${selectedEngine === 'hermes' ? 'background: linear-gradient(135deg,#6366f1,#8b5cf6);' : ''}">${selectedEngine === 'hermes' ? 'HR' : 'AI'}</div>
                <div class="message-content-wrapper">
                    <span class="message-sender-name">${engineLabel}</span>
                    <div class="message-bubble">
                        <div class="typing-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            `;
            chat.appendChild(typingRow);
            chat.scrollTop = chat.scrollHeight;

            const sendBtn = document.getElementById("sendBtn");
            sendBtn.disabled = true;

            try {
                /* -----------------------------------------------
                   JALUR A: Hermes via OpenRouter
                ----------------------------------------------- */
                if (selectedEngine === 'hermes') {
                    // Ambil model yang dipilih user, default ke gpt-4o-mini
                    const selectedModel = document.getElementById('openrouter-model')?.value
                        || 'openai/gpt-4o-mini';

                    const response = await fetch('/openrouter/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            message: message,
                            model:   selectedModel   // ← kirim nama model ke backend
                        })
                    });

                    const data = await response.json();
                    const typingEl = document.getElementById(typingId);
                    if (typingEl) typingEl.remove();

                    const answer = data.reply ?? 'Maaf, tidak ada respons dari Hermes.';
                    appendMessageBubble(answer, 'assistant', 'hermes');

                /* -----------------------------------------------
                   JALUR B: OpenCode (Local MCP)
                ----------------------------------------------- */
                } else {
                    if (!currentSession) {
                        const typingEl = document.getElementById(typingId);
                        if (typingEl) typingEl.remove();
                        alert("Silakan buat atau pilih sesi percakapan terlebih dahulu.");
                        sendBtn.disabled = false;
                        return;
                    }

                    const response = await fetch(`/session/${currentSession}/message`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            message: message,
                            agent: document.getElementById("agent").value
                        })
                    });

                    const data = await response.json();
                    const typingEl = document.getElementById(typingId);
                    if (typingEl) typingEl.remove();

                    let answer = "Maaf, tidak ada respons yang diterima dari agen.";
                    if (data.parts) {
                        const textPart = data.parts.find(p => p.type === "text");
                        if (textPart) answer = textPart.text;
                    }

                    appendMessageBubble(answer, 'assistant', 'opencode');
                }

            } catch (error) {
                const typingEl = document.getElementById(typingId);
                if (typingEl) typingEl.remove();
                appendMessageBubble(`Terjadi kesalahan koneksi: ${error.message}`, "assistant");
            } finally {
                sendBtn.disabled = false;
            }
        }

        /* =========================
           RENDER MESSAGES
        ========================= */
        function renderMessages(messages) {
            const chat = document.getElementById("chat");
            chat.innerHTML = "";

            messages.forEach(item => {
                if (!item.parts) return;

                item.parts.forEach(part => {
                    if (part.type !== "text") return;
                    const role = item.info?.role === "user" ? "user" : "assistant";
                    appendMessageBubble(part.text, role);
                });
            });

            chat.scrollTop = chat.scrollHeight;
        }

        function appendMessageBubble(text, role, engine) {
            const chat = document.getElementById("chat");
            const row = document.createElement("div");
            row.className = `message-row ${role}`;

            const isUser = role === "user";

            let avatarHtml, senderName;
            if (isUser) {
                avatarHtml = `<div class="avatar user-avatar">U</div>`;
                senderName = 'Anda';
            } else if (engine === 'hermes') {
                avatarHtml = `<div class="avatar bot-avatar" style="background: linear-gradient(135deg,#6366f1,#8b5cf6); font-size: 0.65rem;">HR</div>`;
                senderName = 'Hermes 3 Llama (OpenRouter · Cloud)';
            } else {
                avatarHtml = `<div class="avatar bot-avatar">AI</div>`;
                senderName = 'database-assistant (MCP MySQL · Local)';
            }

            let formattedText = text;
            if (!isUser && typeof marked !== "undefined") {
                formattedText = marked.parse(text);
            } else {
                formattedText = `<p>${escapeHtml(text).replace(/\n/g, '<br>')}</p>`;
            }

            // Engine tag badge
            const engineTag = !isUser
                ? `<span style="font-size:0.65rem; padding: 1px 6px; border-radius: 10px; margin-left: 6px; ${
                    engine === 'hermes'
                        ? 'background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.3);'
                        : 'background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.25);'
                  }">● ${engine === 'hermes' ? 'Hermes' : 'OpenCode'}</span>`
                : '';

            row.innerHTML = `
                ${avatarHtml}
                <div class="message-content-wrapper">
                    <span class="message-sender-name">${senderName}${engineTag}</span>
                    <div class="message-bubble">${formattedText}</div>
                </div>
            `;

            chat.appendChild(row);
            chat.scrollTop = chat.scrollHeight;
        }

        function escapeHtml(string) {
            const div = document.createElement('div');
            div.textContent = string;
            return div.innerHTML;
        }

        /* =========================
           INPUT HELPERS
        ========================= */
        function setPrompt(text) {
            const textarea = document.getElementById("message");
            textarea.value = text;
            textarea.focus();
            autoResizeTextarea(textarea);
        }

        function setPromptAndSend(text) {
            setPrompt(text);
            sendMessage();
        }

        function handleKeyDown(event) {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            } else {
                autoResizeTextarea(event.target);
            }
        }

        function autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
        }
    </script>
</body>
</html>