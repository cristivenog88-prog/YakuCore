<?php

namespace App\Services\Tenancy;

use App\Models\Jass;

/**
 * Singleton por request: guarda la JASS activa para el scoping multi-tenant
 * (sección 3 del plan). La fija ResolverJassActual a partir del usuario
 * autenticado (/panel) o del segmento de ruta de supervisión (/supervision/{jass}).
 */
class ContextoJass
{
    private ?int $jassId = null;

    public function fijar(Jass|int $jass): void
    {
        $this->jassId = $jass instanceof Jass ? $jass->id : $jass;
    }

    public function limpiar(): void
    {
        $this->jassId = null;
    }

    public function id(): ?int
    {
        return $this->jassId;
    }

    public function jass(): ?Jass
    {
        return $this->jassId === null ? null : Jass::find($this->jassId);
    }

    public function tieneContexto(): bool
    {
        return $this->jassId !== null;
    }
}
