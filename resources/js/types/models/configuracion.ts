export interface Configuracion {
    id: number;
    granja_id: number;
    telefono_policia: string | null;
    telefono_emergencia: string | null;
    mensaje_alerta: string | null;
    alertas_activas: boolean;
    created_at: string;
    updated_at: string;
}
