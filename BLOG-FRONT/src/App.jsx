import { useState, useEffect } from 'react'
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom'
import Header from './components/Header'
import Footer from './components/Footer'
import Home from './pages/Home'
import BlogPost from './pages/BlogPost'
import CreatePost from './pages/CreatePost'
import './App.css'

function App() {
  return (
    <Router>
      <div className="App">
        <Header />
        <main className="main-content">
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/post/:id" element={<BlogPost />} />
            <Route path="/create" element={<CreatePost />} />
          </Routes>
        </main>
        <Footer />
      </div>
    </Router>
  )
}

export default App
