import type { Granja } from '@/types/models/granja';

export type UserRole = 'super_admin' | 'admin';

export type ContactoTipo = 'correo' | 'telefono';

export interface ContactoUsuario {
    id: number;
    user_id: number;
    tipo: ContactoTipo;
    valor: string;
    created_at: string;
    updated_at: string;
}

export interface Usuario {
    id: number;
    name: string;
    email: string;
    num_doc: string | null;
    role: UserRole;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    granjas?: Granja[];
    contactos?: ContactoUsuario[];
}
