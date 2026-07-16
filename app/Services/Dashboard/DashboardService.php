<?php

namespace App\Services\Dashboard;

use App\Models\Alerta;
use App\Models\Animal;
use App\Models\Collar;
use App\Models\Terreno;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * Obtiene los terrenos de las granjas del usuario con sus coordenadas.
     *
     * @return Collection<int, Terreno>
     */
    public function terrenosDelUsuario(User $user): Collection
    {
        $granjaIds = $this->granjaIds($user);

        return Terreno::query()
            ->whereIn('granja_id', $granjaIds)
            ->select(['id', 'granja_id', 'nombre', 'coordenadas', 'area'])
            ->get();
    }

    /**
     * Obtiene animales con collar asignado y su última ubicación.
     *
     * @return Collection<int, Animal>
     */
    public function animalesConUbicacion(User $user): Collection
    {
        $granjaIds = $this->granjaIds($user);

        return Animal::query()
            ->whereIn('granja_id', $granjaIds)
            ->whereHas('collar')
            ->with([
                'collar' => fn ($q) => $q->select(['id', 'animal_id', 'serie', 'modelo', 'estado']),
                'collar.ultimaUbicacion',
                'terrenos:id,nombre',
            ])
            ->select(['id', 'granja_id', 'nombre', 'codigo', 'tipo', 'raza'])
            ->get();
    }

    /**
     * Obtiene alertas recientes no leídas.
     *
     * @return Collection<int, Alerta>
     */
    public function alertasRecientes(User $user, int $limit = 20): Collection
    {
        $granjaIds = $this->granjaIds($user);

        return Alerta::query()
            ->whereIn('granja_id', $granjaIds)
            ->with(['animal:id,nombre,codigo', 'collar:id,serie', 'terreno:id,nombre'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Marca alertas como leídas.
     */
    public function marcarAlertasLeidas(User $user, ?array $ids = null): int
    {
        $granjaIds = $this->granjaIds($user);

        $query = Alerta::query()
            ->whereIn('granja_id', $granjaIds)
            ->where('leida', false);

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        return $query->update(['leida' => true]);
    }

    /**
     * Cuenta alertas no leídas.
     */
    public function contarAlertasNoLeidas(User $user): int
    {
        $granjaIds = $this->granjaIds($user);

        return Alerta::query()
            ->whereIn('granja_id', $granjaIds)
            ->where('leida', false)
            ->count();
    }

    /** @var array<int, array<int>> */
    private array $granjaIdsCache = [];

    /**
     * @return array<int>
     */
    private function granjaIds(User $user): array
    {
        if (isset($this->granjaIdsCache[$user->id])) {
            return $this->granjaIdsCache[$user->id];
        }

        $ids = $user->isSuperAdmin()
            ? \App\Models\Granja::pluck('id')->toArray()
            : $user->granjas()->pluck('farms.id')->toArray();

        return $this->granjaIdsCache[$user->id] = $ids;
    }
}
