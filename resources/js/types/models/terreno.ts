import type { Granja } from '@/types/models/granja';

export interface Coordenada {
    lat: number;
    lng: number;
}

export interface Terreno {
    id: number;
    granja_id: number;
    nombre: string;
    coordenadas: Coordenada[];
    area: number | null;
    created_at: string;
    updated_at: string;
    granja?: Granja;
}
