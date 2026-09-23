<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpenRouterController extends Controller
{
    /**
     * Endpoint A/B Testing: Hermes 3 via OpenRouter
     * Menggunakan alur Tool Calling 2-step untuk membaca database MySQL lokal.
     *
     * Alur:
     *   [1] Kirim pesan user + definisi tool  →  OpenRouter (API Call #1)
     *   [2] Cek apakah AI meminta tool call (cari_produk)
     *   [3] Jika ya → eksekusi query DB lokal (tabel: produk)
     *   [4] Kirim ulang ke OpenRouter beserta hasil tool  →  (API Call #2)
     *   [5] Return jawaban akhir AI ke frontend
     *
     * Jika AI TIDAK meminta tool call (pertanyaan non-produk / ditolak guardrails),
     * jawaban dari API Call #1 langsung dikembalikan.
     */
    public function sendMessage(Request $request)
    {
        // ---------------------------------------------------------------
        // VALIDASI INPUT
        // ---------------------------------------------------------------
        $request->validate([
            'message' => 'required|string|max:2000',
            'model'   => 'nullable|string|max:100',
        ]);

        $userMessage = $request->message;

        // ---------------------------------------------------------------
        // PILIH MODEL SECARA DINAMIS
        // Whitelist model yang diizinkan untuk keamanan.
        // Jika model tidak ada di daftar atau kosong → pakai default.
        // ---------------------------------------------------------------
        $allowedModels = [
            // ✅ Support Tool Calling
            'openai/gpt-4o-mini',
            'openai/gpt-4o',
            'anthropic/claude-3-haiku',
            'mistralai/mistral-7b-instruct',
            // ⚠️ Direct Reply saja (no tool calling)
            'nousresearch/hermes-3-llama-3.1-405b',
            'meta-llama/llama-3.1-8b-instruct',
        ];

        $defaultModel    = 'openai/gpt-4o-mini';
        $requestedModel  = $request->input('model', '');
        $selectedModel   = in_array($requestedModel, $allowedModels) ? $requestedModel : $defaultModel;

        Log::info("[OpenRouter] Model dipilih: {$selectedModel} (request: '{$requestedModel}')");

        // ---------------------------------------------------------------
        // GUARDRAILS — System Prompt (Pagar Pengaman AI)
        // ---------------------------------------------------------------
        $systemPrompt = "Kamu adalah asisten cerdas untuk Toko Elektronik. "
            . "TUGAS UTAMA: Bantu pengguna mencari informasi produk, stok, dan harga. "
            . "Jika pengguna bertanya soal produk, SELALU gunakan tool `cari_produk` untuk "
            . "mendapatkan data akurat dari database sebelum menjawab. "
            . "ATURAN KETAT: Jika pengguna bertanya hal di luar konteks barang elektronik, "
            . "aksesoris komputer, atau stok toko (misalnya: coding, resep masakan, berita), "
            . "TOLAK dengan sopan dan katakan bahwa kamu hanya melayani pertanyaan seputar produk toko.";

        // ---------------------------------------------------------------
        // CEK API KEY
        // ---------------------------------------------------------------
        $apiKey = env('OPENROUTER_API_KEY');

        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'reply'  => 'API Key OpenRouter belum dikonfigurasi di file .env (OPENROUTER_API_KEY).',
            ], 500);
        }

        // ---------------------------------------------------------------
        // DEFINISI TOOL — cari_produk
        // Memberitahu AI bahwa ada fungsi yang bisa dipanggil
        // untuk mencari produk berdasarkan kata kunci di database.
        // ---------------------------------------------------------------
        $tools = [
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'cari_produk',
                    'description' => 'Mencari data produk (nama, harga, stok, kategori) di database toko berdasarkan kata kunci. '
                        . 'Gunakan tool ini setiap kali pengguna bertanya tentang produk, stok, atau harga.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'kata_kunci' => [
                                'type'        => 'string',
                                'description' => 'Kata kunci nama produk yang ingin dicari, misalnya: "laptop", "mouse", "headset".',
                            ],
                        ],
                        'required'   => ['kata_kunci'],
                    ],
                ],
            ],
        ];

        // ---------------------------------------------------------------
        // SUSUN MESSAGES AWAL
        // ---------------------------------------------------------------
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userMessage],
        ];

        // ================================================================
        // STEP 1 — API CALL PERTAMA
        // Kirim pesan user + definisi tool ke OpenRouter.
        // AI akan memutuskan: panggil tool atau langsung jawab.
        // ================================================================
        try {
            $firstResponse = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => url('/'),
                    'X-Title'       => 'IMRON Chatbot – Toko Elektronik',
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model'       => $selectedModel,
                    'messages'    => $messages,
                    'tools'       => $tools,
                    'tool_choice' => 'auto',   // AI bebas memilih: pakai tool atau jawab langsung
                    'temperature' => 0.2,
                ]);

            if (!$firstResponse->successful()) {
                Log::error('[OpenRouter] API Call #1 gagal: ' . $firstResponse->status() . ' — ' . $firstResponse->body());
                return $this->errorResponse(
                    'Gagal menghubungi OpenRouter (Call #1): ' . ($firstResponse->json('error.message') ?? 'Server AI sedang sibuk.'),
                    $firstResponse->status()
                );
            }

            $firstData        = $firstResponse->json();
            $assistantMessage = $firstData['choices'][0]['message'] ?? null;

            if (!$assistantMessage) {
                return $this->errorResponse('Respons AI tidak terbaca dari OpenRouter (Call #1).');
            }

            // ================================================================
            // STEP 2 — CEK: Apakah AI meminta Tool Call?
            // ================================================================
            $toolCalls = $assistantMessage['tool_calls'] ?? null;

            if (empty($toolCalls)) {
                // ----------------------------------------------------------
                // AI TIDAK memanggil tool → Jawab langsung
                // (misalnya: pertanyaan umum yang ditolak guardrails)
                // ----------------------------------------------------------
                $directReply = $assistantMessage['content'] ?? 'Maaf, tidak ada respons yang diterima dari AI.';

                return response()->json([
                    'status' => 'success',
                    'agent'  => 'Hermes (Direct Reply)',
                    'reply'  => $directReply,
                ]);
            }

            // ================================================================
            // STEP 3 — EKSEKUSI TOOL LOKAL (cari_produk → Query Database MySQL)
            // Ambil tool call pertama yang diminta AI.
            // ================================================================
            $toolCall     = $toolCalls[0];
            $toolCallId   = $toolCall['id'];
            $functionName = $toolCall['function']['name'] ?? '';
            $arguments    = json_decode($toolCall['function']['arguments'] ?? '{}', true);
            $kataKunci    = $arguments['kata_kunci'] ?? '';

            Log::info("[OpenRouter] AI meminta tool: {$functionName}, kata_kunci: \"{$kataKunci}\"");

            // Eksekusi query ke database MySQL lokal (tabel: produk)
            $hasilQuery = DB::table('produk')
                ->where('nama_produk', 'like', '%' . $kataKunci . '%')
                ->get(['id', 'nama_produk', 'kategori', 'harga', 'stok', 'deskripsi']);

            // Ubah hasil query menjadi string JSON untuk dikirim balik ke AI
            $toolResultJson = $hasilQuery->isEmpty()
                ? json_encode(['info' => "Tidak ditemukan produk dengan kata kunci \"{$kataKunci}\" di database toko."])
                : $hasilQuery->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            Log::info("[OpenRouter] Hasil query DB ({$hasilQuery->count()} produk): " . substr($toolResultJson, 0, 300));

            // ================================================================
            // STEP 4 — API CALL KEDUA
            // Kirim ulang ke OpenRouter dengan:
            //   - System prompt (Guardrails)
            //   - User message awal
            //   - Assistant message (yang berisi tool_calls dari API Call #1)
            //   - Tool message (hasil eksekusi database lokal kita)
            // AI akan merangkai jawaban akhir berdasarkan data nyata dari DB.
            // ================================================================
            $messagesWithToolResult = [
                // [1] Guardrails
                ['role' => 'system', 'content' => $systemPrompt],

                // [2] Pesan awal dari user
                ['role' => 'user', 'content' => $userMessage],

                // [3] Respons assistant dari Call #1 (wajib disertakan agar konteks terjaga)
                [
                    'role'       => 'assistant',
                    'content'    => $assistantMessage['content'] ?? null,
                    'tool_calls' => $toolCalls,
                ],

                // [4] Hasil eksekusi tool (dari database MySQL lokal kita)
                [
                    'role'         => 'tool',
                    'tool_call_id' => $toolCallId,
                    'name'         => $functionName,
                    'content'      => $toolResultJson,
                ],
            ];

            $secondResponse = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => url('/'),
                    'X-Title'       => 'IMRON Chatbot – Toko Elektronik',
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model'       => $selectedModel,
                    'messages'    => $messagesWithToolResult,
                    'temperature' => 0.3,
                ]);

            if (!$secondResponse->successful()) {
                Log::error('[OpenRouter] API Call #2 gagal: ' . $secondResponse->status() . ' — ' . $secondResponse->body());
                return $this->errorResponse(
                    'Gagal mendapatkan jawaban akhir dari OpenRouter (Call #2): ' . ($secondResponse->json('error.message') ?? 'Server AI sedang sibuk.'),
                    $secondResponse->status()
                );
            }

            $secondData  = $secondResponse->json();
            $finalAnswer = $secondData['choices'][0]['message']['content']
                ?? 'Maaf, tidak ada respons akhir yang diterima dari AI.';

            // ================================================================
            // STEP 5 — RETURN JAWABAN AKHIR KE FRONTEND
            // ================================================================
            return response()->json([
                'status' => 'success',
                'agent'  => 'Hermes (Tool Calling)',
                'reply'  => $finalAnswer,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[OpenRouter] Connection Timeout: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'reply'  => 'Koneksi ke OpenRouter timeout. Periksa koneksi internet atau coba beberapa saat lagi.',
            ], 504);

        } catch (\Throwable $e) {
            Log::error('[OpenRouter] Exception: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'status' => 'error',
                'reply'  => 'Terjadi kesalahan sistem internal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Return JSON error response yang konsisten.
     */
    private function errorResponse(string $message, int $status = 500): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'reply'  => $message,
        ], ($status >= 400 && $status < 600) ? $status : 500);
    }
}
