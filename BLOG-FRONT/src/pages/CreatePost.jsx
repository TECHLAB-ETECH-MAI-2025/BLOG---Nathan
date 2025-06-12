import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import './CreatePost.css'

function CreatePost() {
  const navigate = useNavigate()
  const [formData, setFormData] = useState({
    title: '',
    content: '',
    excerpt: '',
    author: '',
    tags: ''
  })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

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
    setError('')

    // Validation basique
    if (!formData.title.trim() || !formData.content.trim() || !formData.author.trim()) {
      setError('Veuillez remplir tous les champs obligatoires')
      setLoading(false)
      return
    }

    try {
      // Préparer les données
      const postData = {
        ...formData,
        tags: formData.tags.split(',').map(tag => tag.trim()).filter(tag => tag),
        createdAt: new Date().toISOString()
      }

      // Remplacez par votre appel API réel
      // const response = await fetch('http://localhost:3001/api/posts', {
      //   method: 'POST',
      //   headers: {
      //     'Content-Type': 'application/json',
      //   },
      //   body: JSON.stringify(postData)
      // })
      // 
      // if (!response.ok) {
      //   throw new Error('Erreur lors de la création de l\'article')
      // }
      // 
      // const newPost = await response.json()

      // Simulation pour l'instant
      console.log('Article créé:', postData)
      
      // Redirection après création
      setTimeout(() => {
        navigate('/')
      }, 1000)

    } catch (err) {
      setError('Erreur lors de la création de l\'article: ' + err.message)
    } finally {
      setLoading(false)
    }
  }

  const handleCancel = () => {
    if (window.confirm('Êtes-vous sûr de vouloir annuler ? Toutes les modifications seront perdues.')) {
      navigate('/')
    }
  }

  return (
    <div className="create-post-container">
      <div className="create-post-form">
        <h1>Créer un nouvel article</h1>
        
        {error && (
          <div className="error-message">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="title">Titre *</label>
            <input
              type="text"
              id="title"
              name="title"
              value={formData.title}
              onChange={handleChange}
              placeholder="Entrez le titre de votre article"
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="author">Auteur *</label>
            <input
              type="text"
              id="author"
              name="author"
              value={formData.author}
              onChange={handleChange}
              placeholder="Votre nom"
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="excerpt">Extrait</label>
            <textarea
              id="excerpt"
              name="excerpt"
              value={formData.excerpt}
              onChange={handleChange}
              placeholder="Un court résumé de votre article (optionnel)"
              rows="3"
            />
          </div>

          <div className="form-group">
            <label htmlFor="content">Contenu *</label>
            <textarea
              id="content"
              name="content"
              value={formData.content}
              onChange={handleChange}
              placeholder="Écrivez le contenu de votre article ici..."
              rows="15"
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="tags">Tags</label>
            <input
              type="text"
              id="tags"
              name="tags"
              value={formData.tags}
              onChange={handleChange}
              placeholder="Séparez les tags par des virgules (ex: React, JavaScript, Web)"
            />
            <small className="form-help">Séparez les tags par des virgules</small>
          </div>

          <div className="form-actions">
            <button 
              type="button" 
              onClick={handleCancel}
              className="btn-cancel"
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
    </div>
  )
}

export default CreatePost
