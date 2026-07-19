# Frontend — SGT Chimbo (Ciudad Mágica)

Sitio público en **React + Vite** del Sistema de Gestión Turística de San José
de Chimbo. Consume la API de Laravel (carpeta `../backend`) y renderiza el
inicio, eventos, noticias, galerías, el chatbot y el **mapa turístico interactivo**.

## Requisitos

- Node.js 18+ y npm
- El **backend** corriendo (por defecto en `http://127.0.0.1:3000`) para que
  carguen los datos (lugares del mapa, eventos, etc.). Ver `../backend/README.md`.

## Ejecución local

```bash
npm install
npm run dev
```

Vite queda en **http://localhost:5173**.

> Si el frontend corre en un servidor/contenedor remoto, `localhost:5173`
> apunta a *esa* máquina y no a la tuya. Para verlo en tu navegador, clona y
> ejecuta el proyecto en tu equipo local, o expón el puerto con la IP/túnel del
> servidor.

### Variable de entorno

Crea un archivo `.env` (o `.env.local`) con la URL de la API:

```bash
VITE_API_URL=http://127.0.0.1:3000/api
```

De esta variable se derivan **todas** las llamadas y las URLs de imágenes del
backend — no hay URLs hardcodeadas.

## Scripts

| Script | Descripción |
|--------|-------------|
| `npm run dev` | Servidor de desarrollo con HMR (puerto 5173). |
| `npm run build` | Build de producción en `dist/`. |
| `npm run preview` | Previsualiza el build de producción. |

## Estructura relevante

```
src/
├── App.jsx                    # Enrutador (React Router) + code-splitting
├── components/
│   ├── Navbar.jsx             # Header global + menú desplegable "Turismo"
│   ├── Footer.jsx
│   └── Chatbot.jsx
├── pages/
│   ├── Home.jsx  Eventos.jsx  Noticias.jsx  Galerias.jsx
│   └── ChimboMap.jsx          # Mapa (Leaflet) con filtros por categoría
└── hooks/useTouristPlaces.js  # Estado y datos del mapa
```

### Menú "Turismo" del header

El `Navbar` reemplaza el antiguo enlace "Eventos" por un desplegable **"Turismo"**
con 4 preguntas frecuentes. Cada una navega enviando un *estado* que el mapa
interpreta para filtrar los marcadores:

| Opción | Destino | Muestra |
|--------|---------|---------|
| ¿Qué hacer? | `/eventos` | Agenda de eventos |
| ¿Cómo llegar? | `/mapa` | Todos los lugares atractivos |
| ¿Qué comer? | `/mapa` | Restaurantes y cafeterías |
| ¿Dónde dormir? | `/mapa` | Hoteles, hostales y hosterías |

Detalles del mecanismo en [`../ARQUITECTURA.md`](../ARQUITECTURA.md) (sección 5.1).

---

Documentación general del sistema: [`../ARQUITECTURA.md`](../ARQUITECTURA.md) ·
Despliegue: [`../DEPLOY.md`](../DEPLOY.md)
