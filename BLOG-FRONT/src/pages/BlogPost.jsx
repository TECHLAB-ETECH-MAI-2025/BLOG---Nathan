import { useState, useEffect } from 'react'
import { useParams, Link } from 'react-router-dom'
import './BlogPost.css'

function BlogPost() {
  const { id } = useParams()
  const [post, setPost] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    fetchPost()
  }, [id])

  const fetchPost = async () => {
    try {
      // Remplacez par votre appel API réel
      // const response = await fetch(`http://localhost:3001/api/posts/${id}`)
      // const data = await response.json()
      // setPost(data)

      // Données de test pour l'instant
      setTimeout(() => {
        const mockPost = {
          id: parseInt(id),
          title: `Article ${id} - Titre complet`,
          content: `
            <h2>Introduction</h2>
            <p>Ceci est le contenu complet de l'article ${id}. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
            
            <h3>Section principale</h3>
            <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
            
            <h3>Conclusion</h3>
            <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.</p>
          `,
          author: 'Nathan',
          createdAt: '2024-01-15T10:30:00Z',
          updatedAt: '2024-01-15T14:20:00Z',
          tags: ['React', 'JavaScript', 'Web Development'],
          readTime: '5 min'
        }
        setPost(mockPost)
        setLoading(false)
      }, 1000)
    } catch (err) {
      setError('Erreur lors du chargement de l\'article')
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="blog-post-container">
        <div className="loading">Chargement de l'article...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="blog-post-container">
        <div className="error">
          <h2>Erreur</h2>
          <p>{error}</p>
          <Link to="/" className="back-link">← Retour à l'accueil</Link>
        </div>
      </div>
    )
  }

  if (!post) {
    return (
      <div className="blog-post-container">
        <div className="not-found">
          <h2>Article non trouvé</h2>
          <p>L'article demandé n'existe pas.</p>
          <Link to="/" className="back-link">← Retour à l'accueil</Link>
        </div>
      </div>
    )
  }

  return (
    <div className="blog-post-container">
      <article className="blog-post">
        <header className="post-header">
          <Link to="/" className="back-link">← Retour aux articles</Link>
          <h1 className="post-title">{post.title}</h1>
          <div className="post-meta">
            <div className="meta-info">
              <span className="author">Par {post.author}</span>
              <span className="date">
                {new Date(post.createdAt).toLocaleDateString('fr-FR', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric'
                })}
              </span>
              <span className="read-time">{post.readTime} de lecture</span>
            </div>
            {post.tags && (
              <div className="tags">
                {post.tags.map(tag => (
                  <span key={tag} className="tag">#{tag}</span>
                ))}
              </div>
            )}
          </div>
        </header>
        
        <div className="post-content" dangerouslySetInnerHTML={{ __html: post.content }} />
        
        <footer className="post-footer">
          {post.updatedAt !== post.createdAt && (
            <p className="updated-date">
              Dernière mise à jour : {new Date(post.updatedAt).toLocaleDateString('fr-FR')}
            </p>
          )}
        </footer>
      </article>
    </div>
  )
}

export default BlogPost
