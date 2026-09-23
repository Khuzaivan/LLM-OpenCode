<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OpenCode API Dashboard</title>

    <style>
        :root {
            --bg: #f3f4f6;
            --panel: #ffffff;
            --soft: #f9fafb;
            --border: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --dark: #111111;
            --success: #198754;
            --user: #dbeafe;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .header {
            background: var(--dark);
            color: white;
            padding: 24px 28px;
            border-radius: 14px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .header p {
            margin: 0 0 12px;
            color: rgba(255,255,255,0.8);
        }

        .back {
            color: white;
            text-decoration: none;
            display: inline-block;
            background: #2a2a2a;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
        }

        .card {
            background: var(--panel);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .api-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            border-radius: 8px;
        }

        .api-item:hover {
            background: #f3f4f6;
        }

        .method {
            display: inline-block;
            background: var(--success);
            color: white;
            padding: 4px 7px;
            border-radius: 5px;
            font-size: 11px;
            margin-right: 6px;
            font-weight: 700;
        }

        .label {
            display: block;
            margin-top: 12px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            color: var(--text);
        }

        textarea {
            height: 150px;
            font-family: monospace;
            resize: vertical;
        }

        .button {
            margin-top: 10px;
            padding: 11px 18px;
            border: none;
            background: var(--dark);
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }

        .response-box {
            background: #111827;
            color: #f3f4f6;
            padding: 16px;
            border-radius: 10px;
            overflow: auto;
            max-height: 500px;
            margin-top: 12px;
            white-space: pre-wrap;
            font-family: monospace;
        }

        @media (max-width: 768px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>OpenCode API Dashboard</h1>
            <p>Laravel → OpenCode REST API</p>
            <a href="/" class="back">← Kembali ke Chat</a>
        </header>

        <div class="layout">
            <aside class="card">
                <h3>Available APIs</h3>

                @foreach($apis as $name => $endpoint)
                    <div class="api-item" onclick="selectApi('{{ $endpoint }}')">
                        <span class="method">GET</span>
                        {{ $name }}
                        <br>
                        <small>{{ $endpoint }}</small>
                    </div>
                @endforeach
            </aside>

            <section class="card">
                <h2>API Tester</h2>

                <label class="label">Method</label>
                <select id="method">
                    <option>GET</option>
                    <option>POST</option>
                    <option>PATCH</option>
                    <option>DELETE</option>
                </select>

                <label class="label">Endpoint</label>
                <input id="endpoint" placeholder="/global/health">

                <label class="label">JSON Body</label>
                <textarea id="body" placeholder='{"title":"Test Session"}'></textarea>

                <button class="button" onclick="callApi()">Execute API</button>

                <h3>Response</h3>
                <pre id="response" class="response-box">Belum ada request.</pre>
            </section>
        </div>
    </div>

    <script>
        function selectApi(endpoint) {
            const endpointInput = document.getElementById('endpoint');
            const methodSelect = document.getElementById('method');

            endpointInput.value = endpoint;
            methodSelect.value = 'GET';
        }

        function parseRequestBody(rawBody) {
            const value = rawBody.trim();

            if (!value) {
                return {};
            }

            try {
                return JSON.parse(value);
            } catch (error) {
                alert('JSON Body tidak valid');
                throw error;
            }
        }

        async function callApi() {
            const method = document.getElementById('method').value;
            const endpoint = document.getElementById('endpoint').value;
            const body = document.getElementById('body').value;
            const responseBox = document.getElementById('response');

            try {
                const data = parseRequestBody(body);
                responseBox.textContent = 'Loading...';

                const result = await fetch("{{ route('api.dashboard.call') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        method: method,
                        endpoint: endpoint,
                        data: data
                    })
                }).then(res => res.json());

                responseBox.textContent = JSON.stringify(result, null, 4);
            } catch (error) {
                responseBox.textContent = 'Request error. Periksa kembali JSON body atau endpoint.';
            }
        }
    </script>
</body>
</html>
