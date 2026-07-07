import type { Granja } from '@/types/models/granja';

export interface CompraDocumento {
    id: number;
    purchase_id: number;
    tipo_documento: string;
    serie_documento: string;
    correlativo_documento: string;
    fecha_documento: string | null;
    observaciones: string | null;
    created_at: string;
    updated_at: string;
}

export interface Compra {
    id: number;
    granja_id: number;
    serie: string;
    correlativo: string;
    proveedor: string | null;
    fecha: string;
    observaciones: string | null;
    created_at: string;
    updated_at: string;
    granja?: Granja;
    documentos?: CompraDocumento[];
}
