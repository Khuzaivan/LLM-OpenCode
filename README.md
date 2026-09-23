# 🤖 OpenCode Chatbot - Complete Integration

![Laravel](https://img.shields.io/badge/Laravel-12.x-red?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-yellowgreen)
![OpenCode API](https://img.shields.io/badge/OpenCode-v2-orange)

A complete Laravel-based chatbot web application with full OpenCode API v2 integration, featuring real-time messaging, session management, and responsive UI.

## ✨ Features

### Core Functionality
- 🤖 **AI-Powered Chat**: Send messages and receive intelligent responses
- 📱 **Real-time UI**: Alpine.js reactive components with instant updates
- 💾 **Session Management**: Create, delete, and switch between chat sessions
- 🎯 **Model Selection**: Choose from available AI models
- 📝 **Code Rendering**: Syntax highlighting for code blocks
- 📄 **Markdown Support**: Full markdown rendering for responses
- 🌙 **Dark Theme**: Beautiful dark-themed responsive design
- ⚡ **Fast**: Optimized for performance and responsiveness

### Technical Features
- ✅ **Complete API Integration**: 70+ endpoints to OpenCode API
- ✅ **Service Layer Architecture**: Clean, maintainable PHP code
- ✅ **Dependency Injection**: Laravel service provider pattern
- ✅ **Error Handling**: Graceful fallback when API unavailable
- ✅ **Session Persistence**: API-based session storage
- ✅ **Comprehensive Documentation**: Setup, testing, and deployment guides
- ✅ **Production Ready**: Optimized for real-world deployment

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Node.js**: 18+ (for frontend assets)
- **Composer**: Latest version
- **OpenCode API**: v2 (running on port 9999)
- **Database**: SQLite (default) or MySQL/PostgreSQL

## 🚀 Quick Start

### 1. Install Dependencies

```bash
cd d:\Telyu\Magang\Project LLM

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 2. Setup Environment

```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure OpenCode API

Edit `.env`:
```env
OPENCODE_API_URL=http://127.0.0.1:4096
OPENCODE_API_KEY=your-key-if-required
OPENCODE_TIMEOUT=30
```

### 4. Run Development Server

```bash
php artisan serve --host=localhost --port=8000
```

### 5. Open Application

Visit: **http://localhost:8000**

## 📚 Documentation

| Document | Purpose |
|----------|---------|
| [SETUP.md](./SETUP.md) | Complete installation and deployment guide |
| [TESTING.md](./TESTING.md) | Comprehensive testing checklist |
| [API_INTEGRATION.md](./API_INTEGRATION.md) | Full API reference and examples |
| [QUICKSTART.md](./QUICKSTART.md) | Quick reference for common tasks |
| [INTEGRATION_SUMMARY.md](./INTEGRATION_SUMMARY.md) | What was implemented and why |

## OpenCode V2 API Proxy

Laravel exposes a transparent proxy for the complete OpenCode V2 HTTP API at
`/api/v2/{opencode-path}`. It forwards the HTTP method, query parameters, and
JSON body unchanged to `{OPENCODE_API_URL}/api/{opencode-path}`.

```bash
# OpenCode GET /api/health
curl http://localhost:8000/api/v2/health

# OpenCode GET /api/config
curl http://localhost:8000/api/v2/config

# OpenCode POST /api/session
curl -X POST http://localhost:8000/api/v2/session \
  -H "Content-Type: application/json" \
  -d '{"title":"My session"}'
```

The browser client exposes the same capability through
`api.v2(method, path, { query, body })`. The OpenCode server must expose the
same V2 contract as the official [OpenAPI specification](https://opencode.ai/v2/openapi.json).
Older server versions may implement only a compatibility subset of `/api/*`.

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────┐
│         Frontend (Blade + Alpine.js)            │
│  - Responsive UI with Tailwind CSS              │
│  - Real-time message display                    │
│  - Session management interface                 │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│    Laravel API Routes (/api/*)                  │
│  - 70+ RESTful endpoints                        │
│  - Input validation & error handling            │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│   OpenCodeController (API Handler)              │
│  - Process requests                             │
│  - Format responses                             │
│  - Error management                             │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│   OpenCodeClient (PHP HTTP Client)              │
│  - 40+ methods for API communication            │
│  - Request/response handling                    │
│  - Built-in error handling                      │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│    OpenCode API Server (localhost:9999)         │
│  - External AI/LLM service                      │
│  - Provides models, agents, responses           │
└─────────────────────────────────────────────────┘
```

## 🔌 API Endpoints

### Health & Status
- `GET /api/health` - Check API health
- `GET /api/status` - Check if OpenCode API online

### Models & Agents
- `GET /api/models` - List available models
- `GET /api/agents` - List available agents

### Sessions (Core Features)
- `GET /api/sessions` - List all sessions
- `POST /api/sessions` - Create new session
- `GET /api/sessions/{id}` - Get session details
- `DELETE /api/sessions/{id}` - Delete session
- `POST /api/sessions/{id}/message` - Send message
- `POST /api/sessions/{id}/switch-model` - Change model
- `POST /api/sessions/{id}/switch-agent` - Change agent

See [API_INTEGRATION.md](./API_INTEGRATION.md) for complete endpoint documentation.

## 🧪 Testing

For comprehensive testing guide, see [TESTING.md](./TESTING.md).

Quick test:
```bash
# Test API status
curl http://localhost:8000/api/status

# Test models endpoint
curl http://localhost:8000/api/models
```

## 🚀 Deployment

See [SETUP.md](./SETUP.md) for complete deployment guide.

## 📞 Support

- Check [SETUP.md](./SETUP.md) for installation help
- Review [TESTING.md](./TESTING.md) for testing guide
- See [API_INTEGRATION.md](./API_INTEGRATION.md) for API reference

## 🎯 Project Status

**Status**: ✅ **COMPLETE & READY FOR USE**

---

**Ready to use!** 🚀 Start with [SETUP.md](./SETUP.md)
