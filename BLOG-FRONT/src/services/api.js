const API_BASE_URL = 'http://localhost:8000/react_api';

class ApiService {
  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    
    const config = {
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
      ...options,
    };

    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  // Articles
  async getArticles(page = 1, limit = 10) {
    return this.request(`/articles?page=${page}&limit=${limit}`);
  }

  async getArticle(id) {
    return this.request(`/articles/${id}`);
  }

  async createArticle(articleData) {
    return this.request('/articles', {
      method: 'POST',
      body: JSON.stringify(articleData),
    });
  }

  // Comments - utilise la nouvelle route
  async createComment(commentData) {
    return this.request('/comments', {
      method: 'POST',
      body: JSON.stringify(commentData),
    });
  }
}

export default new ApiService();
