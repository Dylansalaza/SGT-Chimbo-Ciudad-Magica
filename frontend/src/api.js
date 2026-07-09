import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api';

const api = axios.create({
  baseURL: API_URL,
});

// Obtener todos los destinos turísticos
export const getTouristPlaces = async () => {
  const { data } = await api.get('/tourist-places');
  return data;
};

/**
 * Envía la imagen al servidor empaquetada como FormData nativo.
 * Esto simula un formulario real permitiendo que Laravel la procese sin problemas.
 */
// src/api.js
export const buscarLugarPorImagen = async (fileObject) => {
  // DIAGNÓSTICO: Si esto muestra "string" o "undefined" en tu consola, el error es del Hook/Componente
  console.log("Tipo de objeto enviado a la API:", typeof fileObject, fileObject);

  const formData = new FormData();
  formData.append('image', fileObject); // Tiene que ser el objeto File crudo

  const { data } = await api.post('/image-search', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return data;
};

export default api;