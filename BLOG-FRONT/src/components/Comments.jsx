import { useState } from 'react'
import ApiService from '../services/api'
import './Comments.css'

function Comments({ articleId, comments, onCommentAdded }) {
  const [showForm, setShowForm] = useState(false)
  const [formData, setFormData] = useState({
    author: '',
    content: ''
  })
  const [loading, setLoading] = useState(false)
  const [errors, setErrors] = useState([])

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setErrors([])

    try {
      const commentData = {
        ...formData,
        articleId: articleId
      }
      
      const response = await ApiService.createComment(commentData)
      
      // Réinitialiser le formulaire
      setFormData({ author: '', content: '' })
      setShowForm(false)
      
      // Notifier le parent qu'un commentaire a été ajouté
      if (onCommentAdded) {
        onCommentAdded(response)
      }
      
    } catch (error) {
      console.error('Erreur lors de l\'ajout du commentaire:', error)
      setErrors(['Erreur lors de l\'ajout du commentaire'])
    } finally {
      setLoading(false)
    }
  }

  const handleCancel = () => {
    setFormData({ author: '', content: '' })
    setShowForm(false)
    setErrors([])
  }

  return (
    <div className="comments-section">
      <div className="comments-header">
        <h3>Commentaires ({comments.length})</h3>
        {!showForm && (
          <button 
            className="btn-add-comment"
            onClick={() => setShowForm(true)}
          >
            Ajouter un commentaire
          </button>
        )}
      </div>

      {showForm && (
        <form className="comment-form" onSubmit={handleSubmit}>
          {errors.length > 0 && (
            <div className="error-message">
              <ul>
                {errors.map((error, index) => (
                  <li key={index}>{error}</li>
                ))}
              </ul>
            </div>
          )}

          <div className="form-group">
            <label htmlFor="author">Votre nom *</label>
            <input
              type="text"
              id="author"
              name="author"
              value={formData.author}
              onChange={handleChange}
              required
              disabled={loading}
              placeholder="Entrez votre nom"
            />
          </div>

          <div className="form-group">
            <label htmlFor="content">Commentaire *</label>
            <textarea
              id="content"
              name="content"
              value={formData.content}
              onChange={handleChange}
              required
              disabled={loading}
              rows="4"
              placeholder="Écrivez votre commentaire..."
            />
          </div>

          <div className="form-actions">
            <button
              type="button"
              className="btn-cancel"
              onClick={handleCancel}
              disabled={loading}
            >
              Annuler
            </button>
            <button
              type="submit"
              className="btn-submit"
              disabled={loading}
            >
              {loading ? 'Ajout...' : 'Ajouter le commentaire'}
            </button>
          </div>
        </form>
      )}

      {comments.length > 0 ? (
        <div className="comments-list">
          {comments.map(comment => (
            <div key={comment.id} className="comment">
              <div className="comment-header">
                <strong className="comment-author">{comment.author}</strong>
                <span className="comment-date">
                  {new Date(comment.createdAt).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}
                </span>
              </div>
              <div className="comment-content">
                {comment.content}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="no-comments">
          <p>Aucun commentaire pour le moment. Soyez le premier à commenter !</p>
        </div>
      )}
    </div>
  )
}

export default Comments
