import React, { useState, useCallback, useEffect } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import { useDropzone } from 'react-dropzone';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import {
  MapIcon,
  MapPinIcon,
  XMarkIcon,
  CameraIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  MagnifyingGlassIcon,
  TrashIcon,
  TagIcon,
} from '@heroicons/react/24/solid';
import { TargetIcon } from '../components/icons/CustomIcons';

// Solucionar iconos de Leaflet
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

// Componente para centrar el mapa
function MapUpdater({ center, zoom }) {
  const map = useMap();
  useEffect(() => {
    map.setView(center, zoom);
  }, [center, zoom, map]);
  return null;
}

// Lugares turísticos de Chimbo
const lugaresChimbo = [
  { id: 1, nombre: 'Cascada El Chorro', lat: -1.6725, lng: -79.0512, descripcion: 'Hermosa cascada con piscinas naturales', imagen: 'https://images.unsplash.com/photo-1432405972618-c60b0225b8f9', categoria: 'Cascada' },
  { id: 2, nombre: 'Mirador de la Virgen', lat: -1.6795, lng: -79.0442, descripcion: 'Vista panorámica del valle', imagen: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4', categoria: 'Mirador' },
  { id: 3, nombre: 'Parque Central', lat: -1.6762, lng: -79.0469, descripcion: 'Parque principal con juegos infantiles', imagen: 'https://images.unsplash.com/photo-1587271329051-8c5f1f6a1f5d', categoria: 'Parque' },
  { id: 4, nombre: 'Iglesia Matriz', lat: -1.6760, lng: -79.0465, descripcion: 'Iglesia colonial del siglo XVIII', imagen: 'https://images.unsplash.com/photo-1438951192573-3c6a83e2e8d0', categoria: 'Iglesia' },
  { id: 5, nombre: 'Laguna de Chilca', lat: -1.6855, lng: -79.0385, descripcion: 'Hermosa laguna rodeada de naturaleza', imagen: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', categoria: 'Laguna' },
  { id: 6, nombre: 'Cascada de Pailón', lat: -1.6685, lng: -79.0555, descripcion: 'Cascada de 15 metros de altura', imagen: 'https://images.unsplash.com/photo-1546182990-dffeafbe841d', categoria: 'Cascada' },
  { id: 7, nombre: 'Mirador del Calvario', lat: -1.6815, lng: -79.0495, descripcion: 'Vista espectacular de la ciudad', imagen: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b', categoria: 'Mirador' },
];

export default function ChimboMapWithSearch() {
  const [mapCenter, setMapCenter] = useState([-1.6765, -79.0468]);
  const [mapZoom, setMapZoom] = useState(14);
  const [searchResult, setSearchResult] = useState(null);
  const [searching, setSearching] = useState(false);
  const [error, setError] = useState('');
  const [uploadedImage, setUploadedImage] = useState(null);
  const [showImageModal, setShowImageModal] = useState(false);
  const [selectedPlace, setSelectedPlace] = useState(null);
  const [selectedImage, setSelectedImage] = useState(null);

  // Drag & drop para imagen
  const onDrop = useCallback((acceptedFiles) => {
    const file = acceptedFiles[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        setUploadedImage(reader.result);
        setError('');
        setSearchResult(null);
      };
      reader.readAsDataURL(file);
    }
  }, []);

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop,
    accept: { 'image/*': [] },
    multiple: false,
    noClick: false,
  });

  // Buscar lugar por imagen
  const handleSearch = async () => {
    if (!uploadedImage) {
      setError('Por favor, sube o arrastra una imagen primero');
      return;
    }

    setSearching(true);
    setError('');

    try {
      const response = await axios.post('http://127.0.0.1:3000/api/image-search', {
        image: uploadedImage
      });

      if (response.data.success && response.data.best_match) {
        const match = response.data.best_match;
        
        // Buscar el lugar completo en nuestra lista
        const fullPlace = lugaresChimbo.find(p => p.nombre === match.nombre) || match;
        
        setSearchResult(fullPlace);
        setMapCenter([fullPlace.lat, fullPlace.lng]);
        setMapZoom(16);
        
        // Scroll al resultado
        document.getElementById('search-result')?.scrollIntoView({ behavior: 'smooth' });
      } else {
        setError('No se encontraron lugares similares a esta imagen');
      }
    } catch (err) {
      console.error('Error:', err);
      setError('Error al procesar la imagen. Intenta de nuevo.');
    } finally {
      setSearching(false);
    }
  };

  const handlePlaceClick = (place) => {
    setSelectedPlace(place);
    setMapCenter([place.lat, place.lng]);
    setMapZoom(16);
  };

  const handleClearSearch = () => {
    setUploadedImage(null);
    setSearchResult(null);
    setError('');
    setMapCenter([-1.6765, -79.0468]);
    setMapZoom(14);
  };

  return (
    <div className="max-w-7xl mx-auto p-4">
      {/* Título */}
      <div className="text-center mb-6">
        <h1 className="text-3xl font-bold flex items-center justify-center gap-2"><MapIcon className="w-7 h-7" /> San José de Chimbo</h1>
        <p className="text-gray-600 dark:text-gray-400">
          Busca lugares turísticos por imagen o explora el mapa
        </p>
      </div>

      {/* Panel de búsqueda por imagen */}
      <div className="bg-gradient-to-r from-blue-50 to-green-50 dark:from-blue-900 dark:to-green-900 rounded-xl p-6 mb-6 shadow-lg">
        <div className="flex flex-col md:flex-row gap-6 items-center">
          {/* Área de upload */}
          <div className="flex-1">
            <div
              {...getRootProps()}
              className={`border-3 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all ${
                isDragActive 
                  ? 'border-blue-500 bg-blue-100 dark:bg-blue-800' 
                  : uploadedImage 
                    ? 'border-green-500 bg-green-50 dark:bg-green-900/30'
                    : 'border-gray-300 dark:border-gray-600 hover:border-blue-400'
              }`}
            >
              <input {...getInputProps()} />
              {uploadedImage ? (
                <div className="relative">
                  <img 
                    src={uploadedImage} 
                    alt="Imagen seleccionada" 
                    className="max-h-32 mx-auto rounded-lg shadow-md"
                  />
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      setUploadedImage(null);
                      setSearchResult(null);
                    }}
                    className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                  >
                    <XMarkIcon className="w-3.5 h-3.5" />
                  </button>
                </div>
              ) : (
                <div>
                  <CameraIcon className="w-12 h-12 mx-auto mb-2 text-gray-400" />
                  <p className="font-medium flex items-center justify-center gap-1.5">
                    {isDragActive ? <><ArrowDownTrayIcon className="w-4 h-4" /> Suelta la imagen aquí</> : <><ArrowUpTrayIcon className="w-4 h-4" /> Arrastra o haz clic para subir una imagen</>}
                  </p>
                  <p className="text-xs text-gray-500 mt-1">JPG, PNG, GIF hasta 10MB</p>
                </div>
              )}
            </div>
          </div>

          {/* Botones de acción */}
          <div className="flex flex-col gap-2">
            <button
              onClick={handleSearch}
              disabled={!uploadedImage || searching}
              className={`px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 ${
                !uploadedImage || searching
                  ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                  : 'bg-blue-500 text-white hover:bg-blue-600 shadow-md'
              }`}
            >
              {searching ? (
                <>
                  <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Buscando...
                </>
              ) : (
                <>
                  <MagnifyingGlassIcon className="w-4 h-4" /> Buscar en el mapa
                </>
              )}
            </button>

            {uploadedImage && (
              <button
                onClick={handleClearSearch}
                className="px-6 py-2 rounded-xl bg-gray-500 text-white hover:bg-gray-600 transition-all text-sm flex items-center justify-center gap-1.5"
              >
                <TrashIcon className="w-3.5 h-3.5" /> Limpiar
              </button>
            )}
          </div>
        </div>

        {error && (
          <div className="mt-4 p-3 bg-red-100 text-red-700 rounded-lg text-center">
            {error}
          </div>
        )}
      </div>

      {/* Mapa y lista */}
      <div className="grid lg:grid-cols-3 gap-6">
        {/* Mapa */}
        <div className="lg:col-span-2">
          <div className="bg-white dark:bg-[#242424] rounded-xl shadow-lg p-4">
            <div style={{ height: '500px', width: '100%' }}>
              <MapContainer
                center={mapCenter}
                zoom={mapZoom}
                style={{ height: '100%', width: '100%', borderRadius: '8px' }}
                scrollWheelZoom={true}
              >
                <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
                <MapUpdater center={mapCenter} zoom={mapZoom} />
                
                {/* Marcadores de todos los lugares */}
                {lugaresChimbo.map(place => (
                  <Marker
                    key={place.id}
                    position={[place.lat, place.lng]}
                    eventHandlers={{ click: () => handlePlaceClick(place) }}
                  >
                    <Popup>
                      <div className="text-center">
                        <strong className="text-sm">{place.nombre}</strong>
                        <hr className="my-1" />
                        <p className="text-xs">{place.categoria}</p>
                        <button
                          onClick={() => handlePlaceClick(place)}
                          className="mt-1 px-2 py-0.5 bg-blue-500 text-white text-xs rounded"
                        >
                          Ver detalles
                        </button>
                      </div>
                    </Popup>
                  </Marker>
                ))}
                
                {/* Marcador del resultado de búsqueda (destacado) */}
                {searchResult && (
                  <Marker
                    position={[searchResult.lat, searchResult.lng]}
                    eventHandlers={{ click: () => setShowImageModal(true) }}
                  >
                    <Popup>
                      <div className="text-center">
                        <strong className="text-sm text-green-600 flex items-center justify-center gap-1"><TargetIcon className="w-3.5 h-3.5" /> RESULTADO DE BÚSQUEDA</strong>
                        <hr className="my-1" />
                        <strong>{searchResult.nombre}</strong>
                        <p className="text-xs">{searchResult.categoria}</p>
                      </div>
                    </Popup>
                  </Marker>
                )}
              </MapContainer>
            </div>
          </div>
        </div>

        {/* Lista de lugares */}
        <div>
          <div className="bg-white dark:bg-[#242424] rounded-xl shadow-lg p-4 h-full">
            <h2 className="text-lg font-semibold mb-3 flex items-center gap-2">
              <MapPinIcon className="w-4 h-4" /> Lugares Turísticos
              <span className="text-xs text-gray-500">({lugaresChimbo.length})</span>
            </h2>
            <div className="space-y-2 max-h-[450px] overflow-y-auto">
              {lugaresChimbo.map(place => (
                <div
                  key={place.id}
                  onClick={() => handlePlaceClick(place)}
                  className={`p-3 rounded-lg cursor-pointer transition-all border ${
                    selectedPlace?.id === place.id
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/50'
                      : searchResult?.id === place.id
                        ? 'border-green-500 bg-green-50 dark:bg-green-900/50'
                        : 'border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'
                  }`}
                >
                  <div className="flex gap-3">
                    <img
                      src={place.imagen}
                      alt={place.nombre}
                      className="w-16 h-16 object-cover rounded-lg cursor-pointer"
                      onClick={(e) => { e.stopPropagation(); setSelectedImage(place.imagen); }}
                    />
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        <h3 className="font-semibold text-sm">{place.nombre}</h3>
                        {searchResult?.id === place.id && (
                          <span className="bg-green-500 text-white p-0.5 rounded-full"><TargetIcon className="w-3 h-3" /></span>
                        )}
                      </div>
                      <p className="text-xs text-gray-500">{place.categoria}</p>
                      <p className="text-xs mt-1 line-clamp-2">{place.descripcion}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Resultado de búsqueda destacado */}
      {searchResult && (
        <div id="search-result" className="mt-6 p-4 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900 dark:to-blue-900 rounded-xl shadow-lg border-l-4 border-green-500">
          <div className="flex justify-between items-start">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <TargetIcon className="w-6 h-6 text-green-600" />
                <h3 className="font-bold text-xl">Resultado de búsqueda</h3>
              </div>
              <div className="flex gap-4">
                <img src={searchResult.imagen} alt={searchResult.nombre} className="w-24 h-24 object-cover rounded-lg shadow" />
                <div>
                  <h4 className="font-bold text-lg">{searchResult.nombre}</h4>
                  <p className="text-sm text-gray-600 dark:text-gray-300">{searchResult.descripcion}</p>
                  <p className="text-xs mt-1 flex items-center gap-1"><TagIcon className="w-3.5 h-3.5" /> {searchResult.categoria}</p>
                  <p className="text-xs text-green-600 mt-1 flex items-center gap-1"><MapPinIcon className="w-3.5 h-3.5" /> Lat: {searchResult.lat} | Lng: {searchResult.lng}</p>
                </div>
              </div>
            </div>
            <button onClick={() => setSearchResult(null)} className="text-gray-500 hover:text-gray-700"><XMarkIcon className="w-5 h-5" /></button>
          </div>
        </div>
      )}

      {/* Modal de imagen */}
      {selectedImage && (
        <div className="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50" onClick={() => setSelectedImage(null)}>
          <div className="relative max-w-4xl max-h-screen p-4" onClick={(e) => e.stopPropagation()}>
            <img src={selectedImage} alt="Lugar turístico" className="max-w-full max-h-[90vh] rounded-xl shadow-2xl" />
            <button
              onClick={() => setSelectedImage(null)}
              className="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-70"
            >
              <XMarkIcon className="w-4 h-4" />
            </button>
          </div>
        </div>
      )}

      {/* Instrucciones */}
      <div className="mt-6 p-4 bg-gray-100 dark:bg-[#242424] rounded-lg text-sm">
        <h3 className="font-semibold mb-2 flex items-center gap-1.5"><MapPinIcon className="w-4 h-4" /> Cómo usar:</h3>
        <div className="grid md:grid-cols-3 gap-4 text-xs">
          <div className="flex items-center gap-2">
            <span className="shrink-0 w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold">1</span>
            <span>Arrastra una foto o haz clic para subir una imagen</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="shrink-0 w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold">2</span>
            <span>Haz clic en "Buscar en el mapa"</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="shrink-0 w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold">3</span>
            <span>¡El mapa te mostrará el lugar más similar!</span>
          </div>
        </div>
      </div>
    </div>
  );
}