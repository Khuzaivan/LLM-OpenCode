# OpenCode API Integration Guide

Aplikasi ini sudah terintegrasi penuh dengan OpenCode API. Berikut adalah panduan lengkap untuk menggunakan API integration.

## Konfigurasi

### 1. Setup Environment Variables

Edit file `.env` dan pastikan konfigurasi OpenCode sudah benar:

```env
# OpenCode API Configuration
OPENCODE_API_URL=http://127.0.0.1:4096
OPENCODE_API_KEY=your-api-key-here
OPENCODE_TIMEOUT=30
```

**Catatan:**
- `OPENCODE_API_URL`: URL base OpenCode API (ganti dengan URL yang sesuai dengan server OpenCode Anda)
- `OPENCODE_API_KEY`: API key untuk authentication (jika diperlukan)
- `OPENCODE_TIMEOUT`: Timeout untuk HTTP requests dalam detik

### 2. Testing Connection

Untuk memverifikasi koneksi ke OpenCode API, akses endpoint ini:

```
GET http://localhost:8000/api/health
GET http://localhost:8000/api/status
```

Jika berhasil, response akan berupa:
```json
{
  "online": true,
  "status": "ok"
}
```

## API Endpoints

Semua endpoint tersedia melalui route `/api/*`

### Health & Status
- `GET /api/health` - Check server health
- `GET /api/server` - Get server information
- `GET /api/status` - Check if API is online

### Models
- `GET /api/models` - List all available models
- `GET /api/models/default` - Get default model

### Agents
- `GET /api/agents` - List all available agents
- `GET /api/agents/{agentId}` - Get specific agent

### Sessions
- `GET /api/sessions` - List all sessions
- `POST /api/sessions` - Create new session
- `GET /api/sessions/stats` - Get session statistics
- `GET /api/sessions/{sessionId}` - Get specific session
- `DELETE /api/sessions/{sessionId}` - Delete session
- `POST /api/sessions/{sessionId}/rename` - Rename session
- `POST /api/sessions/{sessionId}/switch-agent` - Switch agent
- `POST /api/sessions/{sessionId}/switch-model` - Switch model

### Chat & Messages
- `POST /api/sessions/{sessionId}/message` - Send message
- `POST /api/sessions/{sessionId}/command` - Run command
- `POST /api/sessions/{sessionId}/shell` - Run shell command
- `POST /api/sessions/{sessionId}/skill` - Activate skill

### Generation
- `POST /api/generate` - One-shot text generation

### Providers
- `GET /api/providers` - List all providers
- `GET /api/providers/{providerId}` - Get specific provider

### Skills
- `GET /api/skills` - List all available skills

### Commands
- `GET /api/commands` - List all commands

### Shell
- `GET /api/shell` - List shell commands
- `POST /api/shell` - Execute shell command
- `GET /api/shell/{id}/output` - Get shell output
- `DELETE /api/shell/{id}` - Stop shell command

### Filesystem
- `GET /api/fs/read` - Read file (params: path)
- `GET /api/fs/list` - List directory (params: path)
- `GET /api/fs/find` - Find files (params: pattern)

### Integrations
- `GET /api/integrations` - List integrations
- `GET /api/integrations/{integrationId}` - Get integration
- `POST /api/integrations/{integrationId}/connect-key` - Connect integration

### Plugins
- `GET /api/plugins` - List plugins

### MCP (Model Context Protocol)
- `GET /api/mcp` - List MCP servers
- `GET /api/mcp/resources` - List MCP resources

## Usage Examples

### JavaScript/Fetch

```javascript
// Get available models
const getModels = async () => {
  const response = await fetch('/api/models');
  const data = await response.json();
  console.log(data.data);
};

// Create new session
const createSession = async () => {
  const response = await fetch('/api/sessions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      agent: 'default-agent',
      model: 'default-model'
    })
  });
  const data = await response.json();
  return data.data.id;
};

// Send message
const sendMessage = async (sessionId, message) => {
  const response = await fetch(`/api/sessions/${sessionId}/message`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ message })
  });
  const data = await response.json();
  return data.data;
};

// List sessions
const listSessions = async () => {
  const response = await fetch('/api/sessions');
  const data = await response.json();
  return data.data;
};

// Delete session
const deleteSession = async (sessionId) => {
  const response = await fetch(`/api/sessions/${sessionId}`, {
    method: 'DELETE'
  });
  const data = await response.json();
  return data;
};
```

### cURL Examples

```bash
# Get models
curl http://localhost:8000/api/models

# Create session
curl -X POST http://localhost:8000/api/sessions \
  -H "Content-Type: application/json" \
  -d '{"agent":"default-agent"}'

# Send message
curl -X POST http://localhost:8000/api/sessions/{sessionId}/message \
  -H "Content-Type: application/json" \
  -d '{"message":"Hello, assistant!"}'

# List sessions
curl http://localhost:8000/api/sessions

# Delete session
curl -X DELETE http://localhost:8000/api/sessions/{sessionId}
```

## Response Format

Semua API responses mengikuti format:

### Success Response
```json
{
  "status": "ok",
  "data": {
    // Response data dari OpenCode API
  }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description"
}
```

## Error Handling

API akan mengembalikan HTTP status code yang sesuai:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `404` - Not Found
- `500` - Server Error

## Service Class Documentation

### OpenCodeClient

File: `app/Services/OpenCodeClient.php`

Kelas ini memandle semua komunikasi dengan OpenCode API. Beberapa method penting:

```php
// Health & Status
$client->health()         // Check API health
$client->getServer()      // Get server info
$client->isOnline()       // Check if online

// Models
$client->listModels()     // List models
$client->getDefaultModel()// Get default model

// Agents
$client->listAgents()     // List agents
$client->getAgent($id)    // Get agent by ID

// Sessions
$client->listSessions()               // List all sessions
$client->createSession($data)         // Create session
$client->getSession($id)              // Get session
$client->deleteSession($id)           // Delete session
$client->renameSession($id, $name)    // Rename session
$client->switchSessionAgent($id, $agentId)    // Switch agent
$client->switchSessionModel($id, $modelId)    // Switch model

// Chat
$client->sendMessage($sessionId, $message, $options)    // Send message
$client->runCommand($sessionId, $command)              // Run command
$client->runShellCommand($sessionId, $command)         // Run shell

// Generation
$client->generate($prompt, $options)  // One-shot generation

// Skills
$client->listSkills()     // List skills
$client->activateSkill($sessionId, $skillId, $params)

// And many more...
```

## Integration dengan Frontend

Frontend sudah dipersiapkan untuk menggunakan API ini. Update file `resources/views/chat.blade.php` untuk mengganti mock responses dengan API calls.

Contoh Alpine.js integration:

```javascript
x-data="chatApp()" @function: {
  async listModels() {
    const response = await fetch('/api/models');
    const data = await response.json();
    this.models = data.data;
  },
  
  async createNewSession() {
    const response = await fetch('/api/sessions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    });
    const data = await response.json();
    this.sessions.push(data.data);
  },
  
  async sendMessage(message) {
    const response = await fetch(
      `/api/sessions/${this.currentSessionId}/message`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
      }
    );
    const data = await response.json();
    // Handle response...
  }
}
```

## Troubleshooting

### "Connection refused" error
- Pastikan OpenCode API server sedang running
- Verify URL di `.env` sudah benar
- Check firewall settings

### "401 Unauthorized" error
- Verify API key di `.env` sudah benar
- Check apakah API key sudah expired

### Timeout error
- Increase `OPENCODE_TIMEOUT` value di `.env`
- Check OpenCode server performance
- Verify network connection

### CORS error (saat diakses dari browser)
- Add CORS middleware jika diperlukan
- Configure CORS settings di `config/cors.php`

## Testing API

### Menggunakan Postman
1. Import endpoints dari dokumentasi
2. Set base URL ke `http://localhost:8000/api`
3. Test setiap endpoint

### Menggunakan terminal
```bash
# List models
php artisan tinker
> $client = app('App\Services\OpenCodeClient')
> $client->listModels()->json()
```

## Performa & Caching

Untuk production, pertimbangkan:

1. **Caching responses**
```php
// Di controller
$models = Cache::remember('opencode.models', 3600, function () {
    return $this->client->listModels()->json();
});
```

2. **Rate limiting**
```php
// Di routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // Your API routes
});
```

3. **Queue untuk long-running tasks**
```php
// Dispatch ke queue
dispatch(function () {
    $this->client->runCommand($sessionId, $command);
})->delay(now());
```

## Next Steps

1. Test semua API endpoints dengan Postman atau curl
2. Update frontend untuk consume API real
3. Implement error handling dan user feedback
4. Add caching jika diperlukan
5. Deploy ke production dengan konfigurasi yang sesuai
