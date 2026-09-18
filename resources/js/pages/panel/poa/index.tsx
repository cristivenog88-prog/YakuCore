import { Button } from '@/components/ui/button';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ActividadFila {
    id: number;
    categoria: string;
    descripcion: string;
    frecuenciaAnual: number;
    costoUnitario: number;
    mes: string | null;
    responsable: string | null;
    subtotal: number;
}

interface ResumenPoa {
    anio: number;
    otrosIngresos: number;
    presupuestoBase: number;
    reserva: number;
    presupuestoNeto: number;
    numeroSocios: number;
    cuotaMensual: number | null;
    actividades: ActividadFila[];
}

interface Props {
    anioActual: number;
    usaPoa: boolean;
    poa: ResumenPoa | null;
    puedeGestionar: boolean;
}

const etiquetasCategoria: Record<string, string> = {
    operacion: 'Operación',
    mantenimiento: 'Mantenimiento',
    administracion: 'Administración',
    reposicion_equipos: 'Reposición de equipos',
    rehabilitaciones_menores: 'Rehabilitaciones menores',
};

function formatearMonto(monto: number): string {
    return `S/ ${monto.toFixed(2)}`;
}

const columnas: ColumnaTabla<ActividadFila>[] = [
    { clave: 'categoria', etiqueta: 'Categoría', render: (fila) => etiquetasCategoria[fila.categoria] ?? fila.categoria },
    { clave: 'descripcion', etiqueta: 'Actividad', render: (fila) => fila.descripcion },
    { clave: 'frecuencia', etiqueta: 'Frecuencia/año', render: (fila) => String(fila.frecuenciaAnual) },
    { clave: 'costo', etiqueta: 'Costo unitario', render: (fila) => <span className="font-mono">{formatearMonto(fila.costoUnitario)}</span> },
    { clave: 'mes', etiqueta: 'Mes', render: (fila) => fila.mes ?? '—' },
    { clave: 'responsable', etiqueta: 'Responsable', render: (fila) => fila.responsable ?? '—' },
    { clave: 'subtotal', etiqueta: 'Subtotal', render: (fila) => <span className="font-mono">{formatearMonto(fila.subtotal)}</span> },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'POA', href: '/panel/poa' },
];

export default function PoaIndex({ anioActual, usaPoa, poa, puedeGestionar }: Props) {
    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Plan Operativo Anual" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-lg font-semibold">Plan Operativo Anual {anioActual}</h1>
                        <p className="text-muted-foreground text-sm">
                            Actividades, presupuesto y cuota de mantenimiento calculados a partir del POA.
                        </p>
                    </div>
                    {puedeGestionar && (
                        <Button asChild className="w-fit">
                            <Link href={poa ? route('panel.poa.edit') : route('panel.poa.create')}>
                                {poa ? 'Editar POA' : 'Crear POA'}
                            </Link>
                        </Button>
                    )}
                </div>

                {!usaPoa && (
                    <div className="border-input bg-muted rounded-md border px-4 py-3 text-sm">
                        Esta JASS no tiene activado el uso del POA. La cuota de mantenimiento se sigue ingresando a mano al cobrar.
                        Puede activarse desde Superadmin, en los datos de la JASS.
                    </div>
                )}

                {poa === null ? (
                    <div className="text-muted-foreground rounded-md border px-4 py-6 text-center text-sm">
                        Todavía no se elaboró el POA de {anioActual}.
                    </div>
                ) : (
                    <>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="rounded-md border p-4">
                                <p className="text-muted-foreground text-xs">Presupuesto base</p>
                                <p className="font-mono text-xl">{formatearMonto(poa.presupuestoBase)}</p>
                            </div>
                            <div className="rounded-md border p-4">
                                <p className="text-muted-foreground text-xs">Reserva obligatoria (10%)</p>
                                <p className="font-mono text-xl">{formatearMonto(poa.reserva)}</p>
                            </div>
                            <div className="rounded-md border p-4">
                                <p className="text-muted-foreground text-xs">Otros ingresos (se restan)</p>
                                <p className="font-mono text-xl">{formatearMonto(poa.otrosIngresos)}</p>
                            </div>
                            <div className="rounded-md border p-4">
                                <p className="text-muted-foreground text-xs">Presupuesto neto</p>
                                <p className="font-mono text-xl">{formatearMonto(poa.presupuestoNeto)}</p>
                            </div>
                            <div className="rounded-md border p-4">
                                <p className="text-muted-foreground text-xs">Socios entre los que se reparte</p>
                                <p className="font-mono text-xl">{poa.numeroSocios}</p>
                            </div>
                            <div className="rounded-md border p-4">
                                <p className="text-muted-foreground text-xs">Cuota mensual por socio</p>
                                <p className="font-mono text-xl">
                                    {poa.cuotaMensual !== null ? formatearMonto(poa.cuotaMensual) : '—'}
                                </p>
                                {poa.cuotaMensual === null && (
                                    <p className="text-muted-foreground mt-1 text-xs">No hay socios registrados todavía.</p>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-col gap-3">
                            <h2 className="font-medium">Actividades</h2>
                            <TablaResponsiva
                                columnas={columnas}
                                filas={poa.actividades}
                                obtenerLlave={(fila) => fila.id}
                                vacio="Este POA no tiene actividades."
                            />
                        </div>
                    </>
                )}
            </div>
        </PanelLayout>
    );
}
