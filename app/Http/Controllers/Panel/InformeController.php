<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Informe;
use App\Models\Rendicion;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Informe\GeneradorInforme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeController extends Controller
{
    public function __construct(
        private readonly RegistradorAuditoria $auditoria,
        private readonly GeneradorInforme $generador,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Informe::class);

        return Inertia::render('panel/informes/index', [
            'informes' => Informe::query()
                ->with('rendicion:id,anio,trimestre')
                ->orderByDesc('anio')->orderByDesc('numero')
                ->get()
                ->map(fn (Informe $i) => [
                    'uuid' => $i->uuid,
                    'codigo' => $i->codigo,
                    'numero' => $i->numero,
                    'anio' => $i->anio,
                    'trimestre' => $i->rendicion?->trimestre,
                    'generadoAt' => $i->generado_at?->toIso8601String(),
                    'tienePdf' => $i->pdf_path !== null,
                ]),
        ]);
    }

    public function store(Request $request, Rendicion $rendicion): RedirectResponse
    {
        $this->authorize('create', Informe::class);
        abort_unless($rendicion->jass_id === $request->user()->jass_id, 403);

        try {
            $informe = $this->generador->handle($rendicion, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['rendicion' => $e->getMessage()]);
        }

        $this->auditoria->registrar('generar', $informe, [
            'codigo' => $informe->codigo,
            'rendicion_id' => $rendicion->id,
        ]);

        return redirect()->route('panel.informes.index');
    }

    public function descargar(Informe $informe): StreamedResponse
    {
        $this->authorize('view', $informe);

        abort_unless($informe->pdf_path && Storage::disk('local')->exists($informe->pdf_path), 404);

        $filename = str_replace(' ', '-', $informe->codigo).'.pdf';

        return Storage::disk('local')->download($informe->pdf_path, $filename);
    }
}
