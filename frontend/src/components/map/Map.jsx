import React, { useEffect } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix para iconos de Leaflet en Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

// Componente invisible para mover la cámara del mapa
function ActualizarVista({ center, zoom }) {
  const map = useMap();
  useEffect(() => {
    if (center) map.setView(center, zoom, { animate: true, duration: 1.5 });
  }, [center, zoom, map]);
  return null;
}

export default function Map({ places = [], center, zoom, onSelectPlace }) {
  return (
    <div className="h-[550px] w-full rounded-2xl overflow-hidden shadow-inner relative border border-gray-100">
      <MapContainer 
        center={center} 
        zoom={zoom} 
        className="h-full w-full z-10"
        scrollWheelZoom={true}
      >
        <TileLayer
          attribution='&copy; OpenStreetMap'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        <ActualizarVista center={center} zoom={zoom} />

        {places.map((place) => {
          const lat = parseFloat(place.lat || place.latitud);
          const lng = parseFloat(place.lng || place.longitud);
          if (isNaN(lat) || isNaN(lng)) return null;

          return (
            <Marker 
              key={place.id} 
              position={[lat, lng]}
              eventHandlers={{ click: () => onSelectPlace && onSelectPlace(place) }}
            >
              <Popup>
                <div className="text-center font-sans">
                  <h4 className="font-bold text-gray-800 text-sm m-0">{place.nombre}</h4>
                  <span className="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full inline-block mt-1">
                    {place.categoria}
                  </span>
                </div>
              </Popup>
            </Marker>
          );
        })}
      </MapContainer>
    </div>
  );
}