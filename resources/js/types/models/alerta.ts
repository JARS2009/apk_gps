import type { Animal } from '@/types/models/animal';
import type { Collar } from '@/types/models/collar';
import type { Terreno } from '@/types/models/terreno';

export type AlertaTipo = 'fuera_de_rango' | 'sin_señal';

export interface Alerta {
    id: number;
    granja_id: number;
    collar_id: number;
    animal_id: number | null;
    terreno_id: number | null;
    tipo: AlertaTipo;
    latitud: number;
    longitud: number;
    mensaje: string | null;
    leida: boolean;
    created_at: string;
    updated_at: string;
    animal?: Animal | null;
    collar?: Collar | null;
    terreno?: Terreno | null;
}
