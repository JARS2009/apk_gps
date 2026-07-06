import { ref, onUnmounted } from 'vue';

export interface TrackingState {
    activo: boolean;
    collarId: number | null;
    ultimaLat: number | null;
    ultimaLng: number | null;
    ultimoEnvio: string | null;
    dentroDeTerreno: boolean | null;
    error: string | null;
}

function getCsrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

/**
 * Composable que usa el GPS nativo del celular para emular un collar GPS.
 * Envía la ubicación al backend cada `intervaloMs` milisegundos.
 */
export function useGpsTracking(intervaloMs: number = 60000) {
    const state = ref<TrackingState>({
        activo: false,
        collarId: null,
        ultimaLat: null,
        ultimaLng: null,
        ultimoEnvio: null,
        dentroDeTerreno: null,
        error: null,
    });

    let watchId: number | null = null;
    let intervaloId: ReturnType<typeof setInterval> | null = null;
    let ultimaPosicion: GeolocationPosition | null = null;

    function iniciar(collarId: number) {
        if (!('geolocation' in navigator)) {
            state.value.error = 'GPS no disponible en este dispositivo';
            return;
        }

        state.value.collarId = collarId;
        state.value.activo = true;
        state.value.error = null;

        watchId = navigator.geolocation.watchPosition(
            (pos) => {
                ultimaPosicion = pos;
                state.value.ultimaLat = pos.coords.latitude;
                state.value.ultimaLng = pos.coords.longitude;
            },
            (err) => {
                state.value.error = `Error GPS: ${err.message}`;
            },
            {
                enableHighAccuracy: true,
                maximumAge: 30000,
                timeout: 15000,
            },
        );

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                ultimaPosicion = pos;
                enviarUbicacion(pos.coords.latitude, pos.coords.longitude);
            },
            (err) => {
                state.value.error = `Error GPS: ${err.message}`;
            },
            { enableHighAccuracy: true, timeout: 15000 },
        );

        intervaloId = setInterval(() => {
            if (ultimaPosicion && state.value.collarId) {
                enviarUbicacion(
                    ultimaPosicion.coords.latitude,
                    ultimaPosicion.coords.longitude,
                );
            }
        }, intervaloMs);
    }

    function detener() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        if (intervaloId !== null) {
            clearInterval(intervaloId);
            intervaloId = null;
        }
        state.value.activo = false;
        state.value.collarId = null;
        ultimaPosicion = null;
    }

    async function enviarUbicacion(lat: number, lng: number) {
        if (!state.value.collarId) return;

        try {
            const res = await fetch('/api/tracking/ubicacion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    collar_id: state.value.collarId,
                    latitud: lat,
                    longitud: lng,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                state.value.error = data.message || 'Error al enviar ubicación';
                return;
            }

            state.value.ultimoEnvio = new Date().toLocaleTimeString();
            state.value.dentroDeTerreno = data.dentro_de_terreno;
            state.value.error = null;

            if (data.alerta) {
                if (
                    'Notification' in window &&
                    Notification.permission === 'granted'
                ) {
                    new Notification('Alerta - Fuera de Terreno', {
                        body: data.alerta.mensaje,
                        icon: '/favicon.svg',
                    });
                }
            }
        } catch {
            state.value.error = 'Error de red al enviar ubicación';
        }
    }

    async function solicitarPermisoNotificaciones() {
        if (
            'Notification' in window &&
            Notification.permission === 'default'
        ) {
            await Notification.requestPermission();
        }
    }

    onUnmounted(() => {
        detener();
    });

    return {
        state,
        iniciar,
        detener,
        solicitarPermisoNotificaciones,
    };
}
