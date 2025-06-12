import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import ApiService from '../services/api'
import './Home.css'

function Home() {
  const [posts, setPosts] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [pagination, setPagination] = useState({})

  useEffect(() => {
    fetchPosts()
  }, [])

  const fetchPosts = async () => {
    try {
      setLoading(true)
      const response = await ApiService.getArticles(1, 10)
      setPosts(response.articles)
      setPagination(response.pagination)
    } catch (error) {
      console.error('Erreur lors du chargement des articles:', error)
      setError('Erreur lors du chargement des articles')
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="home">
        <div className="container">
          <div className="loading">Chargement des articles...</div>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="home">
        <div className="container">
          <div className="error-message">
            <h3>Erreur</h3>
            <p>{error}</p>
            <button onClick={fetchPosts}>Réessayer</button>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="home">
      <div className="container">
        <h2 className="home-title">Articles récents</h2>
        {posts.length === 0 ? (
          <div className="no-posts">
            <h3>Aucun article pour le moment</h3>
            <p>Soyez le premier à publier un article !</p>
            <Link to="/create" className="create-first-post">
              Créer le premier article
            </Link>
          </div>
        ) : (
          <div className="posts-grid">
            {posts.map(post => (
              <article key={post.id} className="post-card">
                <h3>
                  <Link to={`/post/${post.id}`} className="post-title">
                    {post.title}
                  </Link>
                </h3>
                <p className="post-excerpt">{post.excerpt}</p>
                <div className="post-meta">
                  <span className="author">Par {post.author}</span>
                  <span className="date">
                    {new Date(post.createdAt).toLocaleDateString('fr-FR')}
                  </span>
                </div>
              </article>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default Home
