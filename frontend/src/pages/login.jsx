import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

// Base de la API, derivada de VITE_API_URL. En local cae al backend de desarrollo.
const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api';

export default function Login() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    
    try {
      const response = await axios.post(`${API_URL}/login`, {
        email,
        password
      });
      
      const { access_token, user } = response.data;
      
      localStorage.setItem('token', access_token);
      localStorage.setItem('user', JSON.stringify(user));
      
      navigate('/');
    } catch (err) {
      setError(err.response?.data?.message || 'Error al iniciar sesión');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center px-4 py-10">
      <div className="w-full max-w-sm bg-white dark:bg-[#242424] rounded-2xl shadow-green-lg ring-1 ring-black/5 dark:ring-white/10 overflow-hidden animate-fade-in-up">
        {/* Franja de marca verde→oro */}
        <div className="h-1.5 brand-gradient-animated" />
        {/* Cabecera con la marca del cantón */}
        <div className="bg-gradient-to-br from-green-700 to-green-900 px-8 pt-8 pb-6 text-center">
          <span className="grid place-items-center w-12 h-12 mx-auto mb-3 rounded-2xl brand-gradient-bg ring-1 ring-inset ring-gold-300/50 text-white font-serif font-black text-lg leading-none shadow-green-md">
            C
          </span>
          <h1 className="font-serif text-2xl font-bold text-white">Iniciar sesión</h1>
          <p className="text-green-100/70 text-xs mt-1">Panel del Sistema de Gestión Turística</p>
        </div>

        <div className="p-8">
          {error && (
            <div className="flex items-start gap-2 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/50 p-3 rounded-lg mb-4 text-sm">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="email" className="block text-gray-700 dark:text-gray-300 mb-1.5 text-sm font-medium">
                Correo electrónico
              </label>
              <input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-800 dark:text-gray-100 outline-none focus:border-green-600 focus:ring-2 focus:ring-green-600/30 transition-colors"
                placeholder="tucorreo@ejemplo.com"
                required
              />
            </div>

            <div>
              <label htmlFor="password" className="block text-gray-700 dark:text-gray-300 mb-1.5 text-sm font-medium">
                Contraseña
              </label>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-800 dark:text-gray-100 outline-none focus:border-green-600 focus:ring-2 focus:ring-green-600/30 transition-colors"
                placeholder="••••••••"
                required
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="btn-press w-full flex items-center justify-center gap-2 bg-green-700 text-white font-semibold py-2.5 rounded-lg hover:bg-green-800 shadow-green-md disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {loading ? (
                <>
                  <span className="animate-spin rounded-full h-4 w-4 border-2 border-white/40 border-t-white" /> Ingresando…
                </>
              ) : 'Ingresar'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}