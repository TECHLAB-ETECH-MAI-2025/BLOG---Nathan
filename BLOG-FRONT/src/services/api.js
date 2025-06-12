const API_BASE_URL = 'http://localhost:3001/api' // Adaptez selon votre backend

export const blogAPI = {
  // Récupérer tous les articles
  getPosts: async () => {
    const response = await fetch(`${API_BASE_URL}/posts`)
    if (!response.ok) throw new Error('Erreur lors du chargement des articles')
    return response.json()
  },

  // Récupérer un article par ID
  getPost: async (id) => {
    const response = await fetch(`${API_BASE_URL}/posts/${id}`)
    if (!response.ok) throw new Error('Article non trouvé')
    return response.json()
  },

  // Créer un nouvel article
  createPost: async (postData) => {
    const response = await fetch(`${API_BASE_URL}/posts`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(postData)
    })
    if (!response.ok) throw new Error('Erreur lors de la création de l\'article')
    return response.json()
  }
}
