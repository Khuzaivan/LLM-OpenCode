/**
 * OpenCode API Client
 * Handles all API communication for the chatbot UI
 */

class OpenCodeApiClient {
  constructor(baseUrl = '/api') {
    this.baseUrl = baseUrl;
  }

  /**
   * Make HTTP request
   */
  async request(method, endpoint, data = null) {
    const url = `${this.baseUrl}${endpoint}`;
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    };

    if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(url, options);
      const json = await response.json();

      if (!response.ok) {
        throw new Error(json.message || `HTTP ${response.status}`);
      }

      return json;
    } catch (error) {
      console.error(`API Error: ${method} ${endpoint}`, error);
      throw error;
    }
  }

  /**
   * Call any path from the official OpenCode V2 API through Laravel.
   * Example: api.v2('GET', '/config') maps to GET /api/config on OpenCode.
   */
  async v2(method, path, { query = {}, body = null } = {}) {
    const url = new URL(`${this.baseUrl}/v2/${String(path).replace(/^\/+/, '')}`, window.location.origin);
    Object.entries(query).forEach(([key, value]) => {
      if (value !== undefined && value !== null) url.searchParams.set(key, value);
    });

    const response = await fetch(url, {
      method,
      headers: { 'Accept': 'application/json', ...(body !== null ? { 'Content-Type': 'application/json' } : {}) },
      body: body !== null ? JSON.stringify(body) : undefined,
    });

    const contentType = response.headers.get('content-type') || '';
    const result = contentType.includes('application/json') ? await response.json() : await response.text();

    if (!response.ok) {
      throw new Error(result?.message || `HTTP ${response.status}`);
    }

    return result;
  }

  // ==================== Health & Status ====================

  async checkHealth() {
    return this.request('GET', '/health');
  }

  async checkStatus() {
    return this.request('GET', '/status');
  }

  async getServer() {
    return this.request('GET', '/server');
  }

  // ==================== Models ====================

  async listModels() {
    const result = await this.request('GET', '/models');
    return result.data;
  }

  async getDefaultModel() {
    const result = await this.request('GET', '/models/default');
    return result.data;
  }

  // ==================== Agents ====================

  async listAgents() {
    const result = await this.request('GET', '/agents');
    return result.data;
  }

  async getAgent(agentId) {
    const result = await this.request('GET', `/agents/${agentId}`);
    return result.data;
  }

  // ==================== Sessions ====================

  async listSessions() {
    const result = await this.request('GET', '/sessions');
    return result.data;
  }

  async createSession(data = {}) {
    const result = await this.request('POST', '/sessions', data);
    return result.data;
  }

  async getSession(sessionId) {
    const result = await this.request('GET', `/sessions/${sessionId}`);
    return result.data;
  }

  async deleteSession(sessionId) {
    return this.request('DELETE', `/sessions/${sessionId}`);
  }

  async getSessionStats() {
    const result = await this.request('GET', '/sessions/stats');
    return result.data;
  }

  async renameSession(sessionId, name) {
    const result = await this.request('POST', `/sessions/${sessionId}/rename`, { name });
    return result.data;
  }

  async switchAgent(sessionId, agent) {
    const result = await this.request('POST', `/sessions/${sessionId}/switch-agent`, { agent });
    return result.data;
  }

  async switchModel(sessionId, model) {
    const result = await this.request('POST', `/sessions/${sessionId}/switch-model`, { model });
    return result.data;
  }

  // ==================== Chat & Messages ====================

  async sendMessage(sessionId, message, options = {}) {
    const result = await this.request('POST', `/sessions/${sessionId}/message`, {
      message,
      ...options
    });
    return result.data;
  }

  async runCommand(sessionId, command) {
    const result = await this.request('POST', `/sessions/${sessionId}/command`, { command });
    return result.data;
  }

  async runShellCommand(sessionId, command) {
    const result = await this.request('POST', `/sessions/${sessionId}/shell`, { command });
    return result.data;
  }

  async activateSkill(sessionId, skill, params = {}) {
    const result = await this.request('POST', `/sessions/${sessionId}/skill`, {
      skill,
      ...params
    });
    return result.data;
  }

  // ==================== Generation ====================

  async generate(prompt, options = {}) {
    const result = await this.request('POST', '/generate', {
      prompt,
      ...options
    });
    return result.data;
  }

  // ==================== Providers ====================

  async listProviders() {
    const result = await this.request('GET', '/providers');
    return result.data;
  }

  async getProvider(providerId) {
    const result = await this.request('GET', `/providers/${providerId}`);
    return result.data;
  }

  // ==================== Skills ====================

  async listSkills() {
    const result = await this.request('GET', '/skills');
    return result.data;
  }

  // ==================== Commands ====================

  async listCommands() {
    const result = await this.request('GET', '/commands');
    return result.data;
  }

  // ==================== Shell ====================

  async listShellCommands() {
    const result = await this.request('GET', '/shell');
    return result.data;
  }

  async executeShellCommand(command, workdir = '') {
    const result = await this.request('POST', '/shell', { command, workdir });
    return result.data;
  }

  async getShellOutput(id) {
    const result = await this.request('GET', `/shell/${id}/output`);
    return result.data;
  }

  async stopShellCommand(id) {
    return this.request('DELETE', `/shell/${id}`);
  }

  // ==================== Filesystem ====================

  async readFile(path) {
    const url = new URL(this.baseUrl + '/fs/read', window.location.origin);
    url.searchParams.append('path', path);
    return fetch(url, {
      headers: { 'Accept': 'application/json' }
    }).then(r => r.json());
  }

  async listDirectory(path = '') {
    const result = await this.request('GET', `/fs/list?path=${encodeURIComponent(path)}`);
    return result.data;
  }

  async findFiles(pattern) {
    const result = await this.request('GET', `/fs/find?pattern=${encodeURIComponent(pattern)}`);
    return result.data;
  }

  // ==================== Integrations ====================

  async listIntegrations() {
    const result = await this.request('GET', '/integrations');
    return result.data;
  }

  async getIntegration(integrationId) {
    const result = await this.request('GET', `/integrations/${integrationId}`);
    return result.data;
  }

  async connectIntegrationWithKey(integrationId, key, data = {}) {
    const result = await this.request('POST', `/integrations/${integrationId}/connect-key`, {
      key,
      ...data
    });
    return result.data;
  }

  // ==================== Plugins ====================

  async listPlugins() {
    const result = await this.request('GET', '/plugins');
    return result.data;
  }

  // ==================== MCP (OpenCode) ====================

  async listMcpServers() {
    const result = await this.request('GET', '/mcp');
    return result.data;
  }

  async listMcpResources() {
    const result = await this.request('GET', '/mcp/resources');
    return result.data;
  }

  // ==================== MCP Database Bridge ====================

  async listDatabaseResources() {
    const result = await this.request('GET', '/mcp/database/resources');
    return result.data;
  }

  async readDatabaseResource(uri, limit = 50, offset = 0) {
    const result = await this.request('GET', `/mcp/database/resource/${encodeURIComponent(uri)}?limit=${limit}&offset=${offset}`);
    return result.data;
  }

  async searchDatabase(query, limit = 20) {
    const result = await this.request('GET', `/mcp/database/search?q=${encodeURIComponent(query)}&limit=${limit}`);
    return result.data;
  }

  async getDatabaseSchema(table) {
    const result = await this.request('GET', `/mcp/database/schema/${encodeURIComponent(table)}`);
    return result.data;
  }

  async executeDatabaseQuery(query) {
    const result = await this.request('POST', '/mcp/database/query', { query });
    return result.data;
  }

  async getDatabaseContext(query = '') {
    const params = query ? `?q=${encodeURIComponent(query)}` : '';
    const result = await this.request('GET', `/mcp/database/context${params}`);
    return result.data;
  }
}

// Export for use in Alpine.js
window.OpenCodeApiClient = OpenCodeApiClient;
