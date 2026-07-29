/**
 * Error thrown by {@link Api} when a request fails, carrying the
 * user-facing message reported by the server.
 */
export class ApiError extends Error {
  constructor (message, status) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }
}

/**
 * Thin fetch() wrapper for the Seedlet JSON API.
 *
 * Adds the CSRF token to state-changing requests and normalizes error
 * handling so callers only ever deal with parsed JSON or an {@link ApiError}.
 */
class Api {
  constructor (csrfToken) {
    this.csrfToken = csrfToken;
  }

  getTorrents () {
    return this.request('/api/torrents');
  }

  getTorrent (id) {
    return this.request(`/api/torrents/${id}`);
  }

  addTorrentFromUri (source) {
    return this.request('/api/torrents', {
      method: 'POST',
      json: { source }
    });
  }

  addTorrentFromFile (file) {
    const body = new FormData();
    body.append('file', file);

    return this.request('/api/torrents', { method: 'POST', body });
  }

  pauseTorrent (id) {
    return this.request(`/api/torrents/${id}/pause`, { method: 'POST' });
  }

  resumeTorrent (id) {
    return this.request(`/api/torrents/${id}/resume`, { method: 'POST' });
  }

  removeTorrent (id, deleteLocalData) {
    return this.request(`/api/torrents/${id}`, {
      method: 'DELETE',
      json: { deleteLocalData }
    });
  }

  updateTorrent (id, changes) {
    return this.request(`/api/torrents/${id}`, {
      method: 'PATCH',
      json: changes
    });
  }

  updateTorrentFiles (id, changes) {
    return this.request(`/api/torrents/${id}/files`, {
      method: 'PATCH',
      json: changes
    });
  }

  getSession () {
    return this.request('/api/session');
  }

  updateSession (changes) {
    return this.request('/api/session', {
      method: 'PATCH',
      json: changes
    });
  }

  async request (url, options = {}) {
    const { json, ...fetchOptions } = options;
    const headers = new Headers(fetchOptions.headers || {});
    const method = (fetchOptions.method || 'GET').toUpperCase();

    if (method !== 'GET' && method !== 'HEAD') {
      headers.set('X-Csrf-Token', this.csrfToken);
    }

    if (json !== undefined) {
      headers.set('Content-Type', 'application/json');
      fetchOptions.body = JSON.stringify(json);
    }

    let response;
    try {
      response = await fetch(url, { ...fetchOptions, headers, credentials: 'same-origin' });
    } catch {
      throw new ApiError('Could not reach the server. Please check your connection.', 0);
    }

    const data = await response.json().catch(() => null);

    if (!response.ok) {
      const message = data && data.error ? data.error : 'An unexpected error occurred.';
      throw new ApiError(message, response.status);
    }

    return data;
  }
}

export default Api;
