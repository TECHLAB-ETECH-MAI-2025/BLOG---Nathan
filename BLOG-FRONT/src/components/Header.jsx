import { Link } from 'react-router-dom'
import './Header.css'

function Header() {
  return (
    <header className="header">
      <div className="container">
        <Link to="/" className="logo">
          <h1>Mon Blog</h1>
        </Link>
        <nav className="nav">
          <Link to="/" className="nav-link">Accueil</Link>
          <Link to="/create" className="nav-link">Créer un article</Link>
        </nav>
      </div>
    </header>
  )
}

export default Header
