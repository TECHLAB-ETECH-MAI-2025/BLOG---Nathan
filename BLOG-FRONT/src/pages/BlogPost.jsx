import { useState, useEffect } from 'react'
import { useParams, Link } from 'react-router-dom'
import ApiService from '../services/api'
import Comments from '../components/Comments'
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
      setLoading(true)
      const response = await ApiService.getArticle(id)
      setPost(response)
    } catch (err) {
      console.error('Erreur lors du chargement de l\'article:', err)
      setError('Erreur lors du chargement de l\'article')
    } finally {
      setLoading(false)
    }
  }

  const handleCommentAdded = (newComment) => {
    // Ajouter le nouveau commentaire à la liste
    setPost(prevPost => ({
      ...prevPost,
      comments: [newComment, ...prevPost.comments]
    }))
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
          <p>L'article que vous recherchez n'existe pas ou a été supprimé.</p>
          <Link to="/" className="back-link">← Retour à l'accueil</Link>
        </div>
      </div>
    )
  }

  return (
    <div className="blog-post-container">
      <Link to="/" className="back-link">← Retour aux articles</Link>
      
      <article className="blog-post">
        <header className="post-header">
          <h1 className="post-title">{post.title}</h1>
          <div className="post-meta">
            <div className="meta-info">
              <span className="author">Par {post.author}</span>
              <span className="date">
                Publié le {new Date(post.createdAt).toLocaleDateString('fr-FR')}
              </span>
              {post.updatedAt && post.updatedAt !== post.createdAt && (
                <span className="updated">
                  Modifié le {new Date(post.updatedAt).toLocaleDateString('fr-FR')}
                </span>
              )}
              {post.likesCount > 0 && (
                <span className="likes">
                  ❤️ {post.likesCount} like{post.likesCount > 1 ? 's' : ''}
                </span>
              )}
            </div>
            {post.categories && post.categories.length > 0 && (
              <div className="post-categories">
                {post.categories.map(category => (
                  <span key={category.id} className="category-tag">
                    {category.name}
                  </span>
                ))}
              </div>
            )}
          </div>
        </header>

        <div className="post-content">
          {post.content.split('\n').map((paragraph, index) => (
            paragraph.trim() && <p key={index}>{paragraph}</p>
          ))}
        </div>

        <Comments 
          articleId={post.id}
          comments={post.comments || []}
          onCommentAdded={handleCommentAdded}
        />
      </article>
    </div>
  )
}

export default BlogPost

