import { useState, useEffect } from 'react';
import Map from '../components/map/Map';
import { getEvents } from '../api';
import { MapIcon, MapPinIcon, CalendarDaysIcon, LightBulbIcon } from '@heroicons/react/24/solid';

export default function MapPage() {
  const [selectedLocation, setSelectedLocation] = useState(null);
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadEvents();
  }, []);

  const loadEvents = async () => {
    try {
      const data = await getEvents();
      setEvents(data.data || data);
    } catch (error) {
      console.error('Error loading events:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleLocationSelect = (latlng) => {
    setSelectedLocation({
      lat: latlng.lat,
      lng: latlng.lng
    });
  };

  if (loading) return <div className="text-center p-8">Cargando mapa...</div>;

  return (
    <div className="max-w-6xl mx-auto p-4">
      <h1 className="font-serif text-2xl font-bold mb-6 flex items-center gap-2"><MapIcon className="w-6 h-6" /> Mapa Turístico</h1>
      
      <div className="grid md:grid-cols-3 gap-6">
        {/* Mapa */}
        <div className="md:col-span-2">
          <div className="bg-white dark:bg-[#242424] rounded-lg shadow-md p-4">
            <h2 className="text-lg font-semibold mb-3 flex items-center gap-1.5"><MapPinIcon className="w-4 h-4" /> Selecciona una ubicación</h2>
            <Map onLocationSelect={handleLocationSelect} />
            {selectedLocation && (
              <div className="mt-3 p-3 bg-blue-50 dark:bg-blue-900 rounded">
                <p className="text-sm">
                  <strong>Coordenadas seleccionadas:</strong><br />
                  Latitud: {selectedLocation.lat.toFixed(6)}<br />
                  Longitud: {selectedLocation.lng.toFixed(6)}
                </p>
              </div>
            )}
          </div>
        </div>

        {/* Lista de eventos cercanos */}
        <div>
          <div className="bg-white dark:bg-[#242424] rounded-lg shadow-md p-4">
            <h2 className="text-lg font-semibold mb-3 flex items-center gap-1.5"><CalendarDaysIcon className="w-4 h-4" /> Eventos cercanos</h2>
            {events.length === 0 ? (
              <p className="text-gray-500 text-sm">No hay eventos registrados</p>
            ) : (
              <div className="space-y-3 max-h-96 overflow-y-auto">
                {events.map((event) => (
                  <div key={event.id} className="border-b pb-2">
                    <h3 className="font-medium">{event.title}</h3>
                    <p className="text-xs text-gray-500">
                      {event.starts_at ? new Date(event.starts_at).toLocaleDateString() : 'Sin fecha'}
                    </p>
                    {event.location && (
                      <p className="text-xs text-green-600 mt-1 flex items-center gap-1">
                        <MapPinIcon className="w-3.5 h-3.5" /> Ubicación disponible
                      </p>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Información de ayuda */}
          <div className="mt-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg p-3">
            <p className="text-xs flex items-start gap-1.5">
              <LightBulbIcon className="w-3.5 h-3.5 shrink-0 mt-0.5" /> <span><strong>Consejo:</strong> Haz clic en el mapa para obtener coordenadas que puedes usar al crear eventos.</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}