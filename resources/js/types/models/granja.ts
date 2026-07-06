import type { Configuracion } from '@/types/models/configuracion';
import type { Usuario } from '@/types/models/user';

export interface Granja {
    id: number;
    id_usuario_creador: number | null;
    nombre: string;
    descripcion: string | null;
    activa: boolean;
    created_at: string;
    updated_at: string;
    creador?: Usuario;
    usuarios?: Usuario[];
    configuracion?: Configuracion;
}
