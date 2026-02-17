const API_BASE = 'https://fusion-tracker.onrender.com';

async function request(endpoint, options = {}) {
  const url = `${API_BASE}${endpoint}`;
  const config = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers,
    },
    ...options,
  };

  const response = await fetch(url, config);

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(error.message || `API Error: ${response.status}`);
  }

  if (response.status === 204) return null;
  return response.json();
}

const api = {
  components: {
    list: () => request('/components'),
    graph: () => request('/components/graph'),
    show: (id) => request(`/components/${id}`),
  },

  designChanges: {
    list: (params = {}) => {
      const query = new URLSearchParams(params).toString();
      return request(`/design-changes${query ? `?${query}` : ''}`);
    },
    create: (data) => request('/design-changes', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
  },

  notifications: {
    list: () => request('/notifications'),
    unreadCount: () => request('/notifications/unread-count'),
    markRead: (id) => request(`/notifications/${id}/read`, { method: 'PATCH' }),
  },
};

export default api;