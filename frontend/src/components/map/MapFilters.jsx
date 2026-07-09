import React from 'react';
import { MagnifyingGlassIcon, CpuChipIcon, CameraIcon } from '@heroicons/react/24/solid';

export default function MapFilters({ hook }) {
    const categorias = ['Todos', 'Naturaleza', 'Cultura', 'Gastronomía', 'Religioso', 'Aventura'];

    return (
        <div className="bg-white rounded-2xl p-5 shadow border border-gray-50 flex flex-col gap-5">
            <h3 className="text-sm font-bold text-gray-800 border-b pb-2 flex items-center gap-1.5"><MagnifyingGlassIcon className="w-4 h-4" /> Explorar Chimbo</h3>

            {/* Búsqueda Tradicional */}
            <div className="flex flex-col gap-3">
                <div>
                    <label className="block text-xs font-bold text-gray-500 mb-1">Buscar destino</label>
                    <input 
                        type="text" 
                        value={hook.searchQuery}
                        onChange={(e) => hook.setSearchQuery(e.target.value)}
                        placeholder="Ej: Cascada..." 
                        className="w-full text-sm px-3 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50/50"
                    />
                </div>

                <div>
                    <label className="block text-xs font-bold text-gray-500 mb-1">Categoría</label>
                    <select 
                        value={hook.selectedCategory}
                        onChange={(e) => hook.setSelectedCategory(e.target.value)}
                        className="w-full text-sm px-3 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    >
                        {categorias.map(cat => (
                            <option key={cat} value={cat}>{cat}</option>
                        ))}
                    </select>
                </div>
            </div>

            <hr className="border-gray-100" />

            {/* Búsqueda Inteligente (IA) */}
            <div className="bg-blue-50/50 rounded-xl p-4 border border-blue-100">
                <label className="block text-xs font-bold text-blue-700 mb-2 flex items-center gap-1.5"><CpuChipIcon className="w-4 h-4" /> Reconocimiento IA</label>
                
                <div className="relative border-2 border-dashed border-blue-200 hover:border-blue-400 rounded-xl p-3 bg-white text-center cursor-pointer">
                    <input 
                        type="file" accept="image/*"
                        onChange={hook.handleImageChange}
                        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    />
                    {hook.base64Image ? (
                        <div className="flex justify-between items-center">
                            <span className="text-xs text-green-600 font-medium flex items-center gap-1"><CameraIcon className="w-3.5 h-3.5" /> Foto lista</span>
                            <button onClick={(e) => { e.preventDefault(); hook.clearIA(); }} className="text-red-500 text-xs font-bold hover:underline z-10 relative">Quitar</button>
                        </div>
                    ) : (
                        <span className="text-xs text-gray-500">Sube una foto del atractivo</span>
                    )}
                </div>

                <button 
                    onClick={hook.handleExecuteIA}
                    disabled={!hook.base64Image || hook.executingIA}
                    className="w-full mt-3 py-2 rounded-xl text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                >
                    {hook.executingIA ? 'Analizando...' : 'Identificar Lugar'}
                </button>

                {hook.iaError && <p className="text-[11px] text-red-500 mt-2 font-medium">{hook.iaError}</p>}
            </div>
        </div>
    );
}