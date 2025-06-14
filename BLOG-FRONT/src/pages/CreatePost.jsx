import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import ApiService from '../services/api'
import './CreatePost.css'

function CreatePost() {
  const navigate = useNavigate()
  const [formData, setFormData] = useState({
    title: '',
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
      const response = await ApiService.createArticle(formData)
      console.log('Article créé:', response)
      navigate(`/post/${response.id}`)
    } catch (error) {
      console.error('Erreur lors de la création:', error)
      if (error.message.includes('errors')) {
        try {
          const errorData = JSON.parse(error.message)
          setErrors(errorData.errors || ['Erreur lors de la création de l\'article'])
        } catch {
          setErrors(['Erreur lors de la création de l\'article'])
        }
      } else {
        setErrors(['Erreur lors de la création de l\'article'])
      }
    } finally {
      setLoading(false)
    }
  }

  const handleCancel = () => {
    navigate('/')
  }

  return (
    <div className="create-post-container">
      <form className="create-post-form" onSubmit={handleSubmit}>
        <h1>Créer un nouvel article</h1>
        
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
          <label htmlFor="title">Titre de l'article *</label>
          <input
            type="text"
            id="title"
            name="title"
            value={formData.title}
            onChange={handleChange}
            required
            disabled={loading}
            placeholder="Entrez le titre de votre article"
          />
        </div>

        <div className="form-group">
          <label htmlFor="content">Contenu de l'article *</label>
          <textarea
            id="content"
            name="content"
            value={formData.content}
            onChange={handleChange}
            required
            disabled={loading}
            rows="15"
            placeholder="Rédigez le contenu de votre article..."
          />
          <small className="form-help">
            Vous pouvez utiliser des retours à la ligne pour séparer les paragraphes.
          </small>
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
            {loading ? 'Publication...' : 'Publier l\'article'}
          </button>
        </div>
      </form>
    </div>
  )
}

export default CreatePost
