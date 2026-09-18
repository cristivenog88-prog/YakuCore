import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle2, FileText, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface Rendicion {
    uuid: string;
    trimestre: number;
    anio: number;
    fechaAsamblea: string | null;
    saldoInicial: number;
    ingresosPagos: number;
    otrosIngresos: number;
    ejecucionPorCategoria: Record<string, number>;
    egresosTotal: number;
    saldoFinal: number;
    checklist: Record<string, boolean>;
    estado: 'borrador' | 'aprobada';
    numeroActa: string | null;
}

interface Props {
    rendicion: Rendicion;
    checklistKeys: string[];
    puedeEditar: boolean;
    puedeAprobar: boolean;
}

const trimestreRoman = (n: number) => ['', 'I', 'II', 'III', 'IV'][n] ?? '';

const formateaMoneda = (v: number) =>
    'S/ ' + v.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const etiquetasChecklist: Record<string, string> = {
    libros_al_dia: 'Libros contables al día',
    recibos_archivados: 'Recibos y comprobantes archivados',
    saldo_bancario_conciliado: 'Saldo bancario conciliado',
    inventario_actualizado: 'Inventario actualizado',
    informe_fiscal: 'Informe del fiscal',
};

const etiquetasCategoria: Record<string, string> = {
    operacion: 'Operación',
    mantenimiento: 'Mantenimiento',
    administracion: 'Administración',
    reposicion_equipos: 'Reposición de equipos',
    rehabilitaciones_menores: 'Rehabilitaciones menores',
    sin_categoria: 'Sin categoría',
};

export default function RendicionShow({ rendicion, checklistKeys, puedeEditar, puedeAprobar }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Panel', href: '/panel' },
        { title: 'Rendiciones', href: '/panel/rendiciones' },
        { title: `${trimestreRoman(rendicion.trimestre)} ${rendicion.anio}`, href: route('panel.rendiciones.show', rendicion.uuid) },
    ];
    const esBorrador = rendicion.estado === 'borrador';

    const borrador = useForm({
        checklist: { ...rendicion.checklist } as Record<string, boolean>,
        fecha_asamblea: rendicion.fechaAsamblea ?? '',
        otros_ingresos: rendicion.otrosIngresos,
    });

    const toggle = (k: string) => borrador.setData('checklist', { ...borrador.data.checklist, [k]: !borrador.data.checklist[k] });
    const guardarBorrador = () => borrador.put(route('panel.rendiciones.update-borrador', rendicion.uuid), { preserveScroll: true });

    const aprobar = useForm({ numero_acta: '' });
    const submitAprobar: FormEventHandler = (e) => {
        e.preventDefault();
        aprobar.post(route('panel.rendiciones.aprobar', rendicion.uuid));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rendición ${trimestreRoman(rendicion.trimestre)} ${rendicion.anio}`} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            Rendición trimestral — {trimestreRoman(rendicion.trimestre)} {rendicion.anio}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Saldo final del trimestre: {formateaMoneda(rendicion.saldoFinal)}
                        </p>
                    </div>
                    <span
                        className={`inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ${
                            esBorrador
                                ? 'bg-yaku-rol-vocal-suave text-yaku-rol-vocal'
                                : 'bg-yaku-rol-presidente-suave text-yaku-rol-presidente'
                        }`}
                    >
                        {esBorrador ? 'Borrador' : `Aprobada — ${rendicion.numeroActa ?? ''}`}
                    </span>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-md border">
                        <div className="border-b bg-muted/40 px-4 py-2 text-sm font-medium">Balance del trimestre</div>
                        <dl className="divide-y text-sm">
                            <div className="flex justify-between px-4 py-2"><dt>Saldo inicial</dt><dd className="font-mono">{formateaMoneda(rendicion.saldoInicial)}</dd></div>
                            <div className="flex justify-between px-4 py-2"><dt>Ingresos por pagos</dt><dd className="font-mono">{formateaMoneda(rendicion.ingresosPagos)}</dd></div>
                            <div className="flex justify-between px-4 py-2"><dt>Otros ingresos</dt><dd className="font-mono">{formateaMoneda(rendicion.otrosIngresos)}</dd></div>
                            <div className="flex justify-between px-4 py-2"><dt>Egresos totales</dt><dd className="font-mono">({formateaMoneda(rendicion.egresosTotal)})</dd></div>
                            <div className="flex justify-between px-4 py-2 font-medium"><dt>Saldo final</dt><dd className="font-mono">{formateaMoneda(rendicion.saldoFinal)}</dd></div>
                        </dl>
                    </div>

                    <div className="rounded-md border">
                        <div className="border-b bg-muted/40 px-4 py-2 text-sm font-medium">Ejecución por categoría de POA</div>
                        <dl className="divide-y text-sm">
                            {Object.entries(rendicion.ejecucionPorCategoria).map(([cat, monto]) => (
                                <div key={cat} className="flex justify-between px-4 py-2">
                                    <dt>{etiquetasCategoria[cat] ?? cat}</dt>
                                    <dd className="font-mono">{formateaMoneda(monto)}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </div>

                <div className="rounded-md border p-4">
                    <div className="mb-3 text-sm font-medium">Control documental</div>
                    <div className="grid gap-2 md:grid-cols-2">
                        {checklistKeys.map((k) => (
                            <label key={k} className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={!!borrador.data.checklist[k]}
                                    disabled={!esBorrador || !puedeEditar}
                                    onChange={() => toggle(k)}
                                    className="h-4 w-4"
                                />
                                {etiquetasChecklist[k] ?? k}
                            </label>
                        ))}
                    </div>

                    {esBorrador && puedeEditar && (
                        <div className="mt-4 grid gap-3 md:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="fa">Fecha de Asamblea</Label>
                                <Input id="fa" type="date" value={borrador.data.fecha_asamblea} onChange={(e) => borrador.setData('fecha_asamblea', e.target.value)} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="oi">Otros ingresos (S/)</Label>
                                <Input id="oi" type="number" step="0.01" min={0} value={borrador.data.otros_ingresos} onChange={(e) => borrador.setData('otros_ingresos', Number(e.target.value))} />
                            </div>
                            <div className="flex items-end">
                                <Button onClick={guardarBorrador} disabled={borrador.processing} variant="outline">
                                    Guardar borrador
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                {!esBorrador && (
                    <div className="flex flex-col gap-3 rounded-md border bg-muted/30 p-4 md:flex-row md:items-center md:justify-between">
                        <div className="text-sm">
                            <span className="font-medium">Informe de gestión financiera: </span>
                            Genera el PDF oficial (INFORME N.° NNNN-YYYY-JASS) desde esta rendición aprobada.
                        </div>
                        <Button onClick={() => router.post(route('panel.informes.store', rendicion.uuid))} variant="outline">
                            <FileText className="h-4 w-4" />
                            Generar informe PDF
                        </Button>
                    </div>
                )}

                {esBorrador && puedeAprobar && (
                    <form onSubmit={submitAprobar} className="flex flex-col gap-3 rounded-md border p-4 md:flex-row md:items-end">
                        <div className="grid gap-2 md:flex-1">
                            <Label htmlFor="acta">N.° de acta de asamblea</Label>
                            <Input id="acta" value={aprobar.data.numero_acta} onChange={(e) => aprobar.setData('numero_acta', e.target.value)} required />
                            {aprobar.errors.numero_acta && <span className="text-xs text-destructive">{aprobar.errors.numero_acta}</span>}
                            {aprobar.errors.checklist && <span className="text-xs text-destructive">{aprobar.errors.checklist}</span>}
                            {aprobar.errors.fecha_asamblea && <span className="text-xs text-destructive">{aprobar.errors.fecha_asamblea}</span>}
                        </div>
                        <Button type="submit" disabled={aprobar.processing}>
                            {aprobar.processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            <CheckCircle2 className="h-4 w-4" />
                            Aprobar en Asamblea
                        </Button>
                    </form>
                )}
            </div>
        </PanelLayout>
    );
}
