import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import './Home.css'

function Home() {
  const [posts, setPosts] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    // Remplacez cette URL par l'endpoint de votre API backend
    fetchPosts()
  }, [])

  const fetchPosts = async () => {
    try {
      // Exemple d'appel API - adaptez selon votre backend
      // const response = await fetch('http://localhost:3001/api/posts')
      // const data = await response.json()
      // setPosts(data)
      
      // Données de test pour l'instant
      setPosts([
        {
          id: 1,
          title: "Premier article de blog",
          excerpt: "Ceci est un extrait du premier article...",
          createdAt: "2024-01-15",
          author: "Nathan"
        },
        {
          id: 2,
          title: "Deuxième article",
          excerpt: "Un autre article intéressant...",
          createdAt: "2024-01-16",
          author: "Nathan"
        }
      ])
      setLoading(false)
    } catch (error) {
      console.error('Erreur lors du chargement des articles:', error)
      setLoading(false)
    }
  }

  if (loading) {
    return <div className="loading">Chargement des articles...</div>
  }

  return (
    <div className="home">
      <div className="container">
        <h2>Articles récents</h2>
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
                <span>Par {post.author}</span>
                <span>{new Date(post.createdAt).toLocaleDateString('fr-FR')}</span>
              </div>
            </article>
          ))}
        </div>
      </div>
    </div>
  )
}

export default Home
