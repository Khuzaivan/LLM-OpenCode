# 🚀 OpenCode Chatbot UI - Complete Setup & Deployment Guide

## Overview

Aplikasi chatbot web yang terintegrasi penuh dengan OpenCode API v2. Siap untuk production deployment.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js & npm
- OpenCode API Server (v2) running on port 9999

## Quick Start (5 menit)

### 1. Install Dependencies

```bash
cd d:\Telyu\Magang\Project LLM

# PHP dependencies
composer install

# JavaScript dependencies
npm install
```

### 2. Setup Environment

```bash
# Copy env file
copy .env.example .env

# Generate app key
php artisan key:generate
```

### 3. Configure OpenCode API

Edit `.env`:
```env
OPENCODE_API_URL=http://localhost:4096
OPENCODE_API_KEY=your-api-key-here
OPENCODE_TIMEOUT=30
```

### 4. Run Development Server

```bash
# Terminal 1: Start Laravel
php artisan serve

# Terminal 2: Build Assets (optional, untuk development)
npm run dev
```

### 5. Access Application

Open browser: **http://localhost:8000**

---

## Full Installation Steps

### Step 1: Clone/Setup Project

```bash
cd d:\Telyu\Magang\Project LLM
```

### Step 2: Install Composer Dependencies

```bash
composer install
```

This will install:
- Laravel Framework 12.x
- All necessary packages

### Step 3: Install Node Dependencies

```bash
npm install
```

### Step 4: Generate App Key

```bash
php artisan key:generate
```

### Step 5: Environment Configuration

```bash
# Copy example to .env
copy .env.example .env
```

Edit `.env` and update:

```env
APP_NAME="OpenCode Chatbot"
APP_DEBUG=true
APP_URL=http://localhost:8000

# OpenCode API Configuration
OPENCODE_API_URL=http://localhost:4096
OPENCODE_API_KEY=your-api-key-if-required
OPENCODE_TIMEOUT=30
```

### Step 6: Database Setup (Optional)

```bash
php artisan migrate
```

### Step 7: Start Development Server

```bash
php artisan serve --host=localhost --port=8000
```

Expected output:
```
INFO  Server running on [http://localhost:8000]
```

### Step 8: Access Application

Visit: **http://localhost:8000**

---

## Architecture Overview

```
Frontend (Blade + Alpine.js + Tailwind)
        ↓
Laravel Routes & Controllers
        ↓
OpenCode API Client Service
        ↓
OpenCode HTTP API
        ↓
OpenCode Server (localhost:9999)
```

## How It Works

### 1. **Models Loading**
```
App Start → API: GET /api/models → Display in dropdown
```

### 2. **Session Creation**
```
User clicks "Mulai Percakapan Baru" → API: POST /api/sessions → Create session
```

### 3. **Send Message**
```
User sends message → API: POST /api/sessions/{id}/message → Stream response
```

### 4. **Session Management**
```
Get Sessions → API: GET /api/sessions
Delete Session → API: DELETE /api/sessions/{id}
Switch Model → API: POST /api/sessions/{id}/switch-model
```

## API Endpoints Available

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Check API health |
| GET | `/api/status` | Check if OpenCode API online |
| GET | `/api/models` | List available models |
| GET | `/api/agents` | List available agents |
| GET | `/api/sessions` | List all sessions |
| POST | `/api/sessions` | Create new session |
| GET | `/api/sessions/{id}` | Get session details |
| DELETE | `/api/sessions/{id}` | Delete session |
| POST | `/api/sessions/{id}/message` | Send message |
| POST | `/api/sessions/{id}/switch-model` | Switch model |

See `API_INTEGRATION.md` for full API reference.

## Frontend Architecture

### Components

**chat.blade.php**
- Main UI template
- Alpine.js application logic
- Markdown rendering (Marked.js)
- Code highlighting (Highlight.js)
- Tailwind CSS styling

**api-client.js**
- JavaScript API client class
- 40+ methods for API communication
- Error handling & retry logic
- Request/response formatting

### Key Features

✅ Real-time message streaming
✅ Session persistence (API-based)
✅ Model selection
✅ Code syntax highlighting
✅ Markdown rendering
✅ Responsive dark theme
✅ Error handling & fallback modes
✅ Loading states & indicators

## Testing

### Test 1: Check API Connection

```bash
# Test status endpoint
curl http://localhost:8000/api/status

# Expected: {"online":true/false}
```

### Test 2: Load Models

```bash
curl http://localhost:8000/api/models

# Expected: List of available models
```

### Test 3: Create Session

```bash
curl -X POST http://localhost:8000/api/sessions \
  -H "Content-Type: application/json" \
  -d '{}'

# Expected: New session ID
```

### Test 4: Send Message

```bash
curl -X POST http://localhost:8000/api/sessions/{SESSION_ID}/message \
  -H "Content-Type: application/json" \
  -d '{"message":"Hello!"}'

# Expected: Assistant response
```

## Troubleshooting

### Problem: "Connection refused" error

**Solution:**
1. Verify OpenCode API server is running on port 9999
2. Check firewall settings
3. Verify URL in `.env` is correct

```bash
# Test OpenCode API directly
curl http://localhost:4096/api/health
```

### Problem: 404 API endpoints

**Solution:**
1. Laravel server not recognizing api.php routes
2. Clear route cache:

```bash
php artisan route:clear
php artisan cache:clear
```

### Problem: "Gagal membuat sesi baru"

**Solution:**
1. Check OpenCode API server is running
2. Verify OPENCODE_API_KEY if required
3. Check logs: `storage/logs/laravel.log`

### Problem: Timeout when sending messages

**Solution:**
1. Increase OPENCODE_TIMEOUT in `.env`:
   ```env
   OPENCODE_TIMEOUT=60
   ```
2. Check OpenCode server performance
3. Verify network connectivity

### Problem: CSS/JS not loading

**Solution:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Check console for errors (F12)
3. Rebuild assets:
   ```bash
   npm run build
   ```

## Production Deployment

### Prerequisites

- Server with PHP 8.2+
- SSL/TLS certificate
- Domain name

### Deployment Steps

1. **Clone repository**
   ```bash
   git clone <repo-url>
   cd <project-dir>
   ```

2. **Install dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm ci
   npm run build
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure for production**
   ```env
   APP_DEBUG=false
   APP_ENV=production
   OPENCODE_API_URL=https://opencode-api.your-domain.com
   ```

5. **Setup permissions**
   ```bash
   chmod -R 775 storage
   chmod -R 775 bootstrap/cache
   ```

6. **Setup web server (Nginx example)**
   ```nginx
   server {
       listen 443 ssl;
       server_name chatbot.example.com;
       
       root /var/www/chatbot/public;
       index index.php;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
       }
   }
   ```

7. **Setup SSL certificate**
   ```bash
   certbot certonly --webroot -w /var/www/chatbot -d chatbot.example.com
   ```

8. **Monitor logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Performance Optimization

### Caching

```php
// In controller
$models = Cache::remember('opencode.models', 3600, function () {
    return $this->client->listModels()->json();
});
```

### Rate Limiting

```php
// In routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // API routes
});
```

### Database Indexing

```bash
php artisan migrate
```

## Security Considerations

✅ API keys stored in `.env`
✅ HTTPS recommended for production
✅ Input validation on all endpoints
✅ CORS configured as needed
✅ Rate limiting enabled
✅ No sensitive data in logs

## File Structure

```
d:\Telyu\Magang\Project LLM\
├── app/
│   ├── Http/Controllers/
│   │   ├── ChatController.php
│   │   └── Api/OpenCodeController.php
│   ├── Services/
│   │   └── OpenCodeClient.php
│   └── Providers/
│       └── AppServiceProvider.php
├── routes/
│   ├── web.php
│   └── api.php
├── resources/
│   ├── js/
│   │   ├── api-client.js
│   │   ├── app.js
│   │   └── bootstrap.js
│   └── views/
│       └── chat.blade.php
├── public/
│   ├── js/
│   │   └── api-client.js (copied)
│   └── index.php
├── config/
│   ├── app.php
│   ├── services.php
│   └── ...
├── bootstrap/
│   └── app.php
├── .env
├── .env.example
├── composer.json
├── package.json
├── API_INTEGRATION.md
├── QUICKSTART.md
├── INTEGRATION_SUMMARY.md
└── SETUP.md (this file)
```

## Next Steps

1. ✅ Install dependencies
2. ✅ Configure environment
3. ✅ Start Laravel server
4. ✅ Access application
5. **Verify OpenCode API connection**
6. **Test chatbot functionality**
7. **Deploy to production** (if ready)

## Support & Documentation

- [API_INTEGRATION.md](./API_INTEGRATION.md) - Full API reference
- [QUICKSTART.md](./QUICKSTART.md) - Quick testing guide
- [Laravel Docs](https://laravel.com/docs)
- [Alpine.js Docs](https://alpinejs.dev)
- [OpenCode API Docs](https://opencode.ai/v2/docs/api)

## Useful Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan route:clear
php artisan config:clear

# View Laravel logs
tail -f storage/logs/laravel.log

# Check database migrations
php artisan migrate:status

# Generate new app key
php artisan key:generate

# Restart PHP server
php artisan serve --host=localhost --port=8000
```

## Version Information

- **Laravel**: 12.x
- **PHP**: 8.2+
- **Node.js**: 18+
- **OpenCode API**: v2
- **Alpine.js**: 3.x
- **Tailwind CSS**: 3.x

## License

MIT License - See LICENSE file for details

---

**Ready to run!** 🎉

Follow the Quick Start section above to get up and running in 5 minutes.
