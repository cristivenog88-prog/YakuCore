<?php

namespace App\Services\Publico;

use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Socio;
use App\Models\Vivienda;
use App\Services\Morosidad\EstadoCuenta;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Buscador público de deuda personal (spec §6.13, decisión 8.5). El límite
 * de 3 intentos cuenta solo los FALLIDOS (DNI inexistente o medidor que no
 * corresponde) — por eso no se usa el middleware `throttle`, que cuenta
 * toda petición; se controla manualmente con el mismo RateLimiter de
 * Laravel bajo la clave ip|jass_id|dni, registrada también como limiter
 * nombrado 'consulta-deuda' en AppServiceProvider para que quede
 * documentada y sea auditable.
 */
class ConsultaDeuda
{
    private const MAX_INTENTOS = 3;

    private const DECAY_MINUTOS = 15;

    public function __construct(private readonly EstadoCuenta $estadoCuenta) {}

    public function clave(string $ip, int $jassId, string $dni): string
    {
        return "{$ip}|{$jassId}|{$dni}";
    }

    public function asegurarNoBloqueado(string $clave): void
    {
        if (! RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            return;
        }

        $minutos = (int) ceil(RateLimiter::availableIn($clave) / 60);

        throw ValidationException::withMessages([
            'dni' => "Demasiados intentos. Vuelve a intentarlo en {$minutos} minuto(s).",
        ]);
    }

    /**
     * @return array<int, array{vivienda: string, periodosMantenimiento: string[], periodosConsumo: string[]}>
     */
    public function consultar(Jass $jass, string $clave, string $dni, ?string $correlativoMedidor): array
    {
        app(ContextoJass::class)->fijar($jass);

        $socio = Socio::where('dni', $dni)->first();

        if ($socio === null) {
            RateLimiter::hit($clave, self::DECAY_MINUTOS * 60);

            throw ValidationException::withMessages([
                'dni' => 'No se encontró ningún socio con ese DNI en esta JASS.',
            ]);
        }

        if ($jass->tiene_medidor && $correlativoMedidor !== null && $correlativoMedidor !== '') {
            $medidorValido = Medidor::whereHas('vivienda', fn ($query) => $query->where('socio_id', $socio->id))
                ->where('correlativo', $correlativoMedidor)
                ->exists();

            if (! $medidorValido) {
                RateLimiter::hit($clave, self::DECAY_MINUTOS * 60);

                throw ValidationException::withMessages([
                    'correlativo_medidor' => 'El número de medidor no corresponde a este DNI.',
                ]);
            }
        }

        RateLimiter::clear($clave);

        return Vivienda::where('socio_id', $socio->id)
            ->where('activa', true)
            ->get()
            ->values()
            ->map(function (Vivienda $vivienda, int $indice) {
                $pendientes = $this->estadoCuenta->periodosPendientes($vivienda);

                return [
                    'vivienda' => 'Vivienda '.($indice + 1),
                    'periodosMantenimiento' => $pendientes['mantenimiento'],
                    'periodosConsumo' => $pendientes['consumo'],
                ];
            })
            ->all();
    }
}
