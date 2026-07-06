import type { Collar } from '@/types/models/collar';
import type { Granja } from '@/types/models/granja';
import type { Terreno } from '@/types/models/terreno';

export interface Animal {
    id: number;
    granja_id: number;
    nombre: string;
    codigo: string;
    tipo: string | null;
    raza: string | null;
    created_at: string;
    updated_at: string;
    granja?: Granja;
    terrenos?: Terreno[];
    collar?: Collar | null;
}
