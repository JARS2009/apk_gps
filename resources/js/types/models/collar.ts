import type { Animal } from '@/types/models/animal';

export type CollarEstado = 'disponible' | 'asignado' | 'inactivo';

export interface Collar {
    id: number;
    animal_id: number | null;
    serie: string;
    modelo: string;
    estado: CollarEstado;
    created_at: string;
    updated_at: string;
    animal?: Animal | null;
}
