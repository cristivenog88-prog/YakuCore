import { Button } from '@/components/ui/button';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface GastoFila {
    id: number;
    descripcion: string;
    monto: number;
    tipoComprobante: string;
    tieneFoto: boolean;
    usuario: string;
    fecha: string;
}

interface Props {
    gastos: GastoFila[];
    totalesPorMes: Record<string, number>;
    totalAnio: number;
    anio: number;
    puedeGestionar: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Gastos', href: '/panel/gastos' },
];

const etiquetasComprobante: Record<string, string> = {
    boleta: 'Boleta',
    factura: 'Factura',
    declaracion_jurada: 'Declaración jurada',
};

function formatearMonto(monto: number): string {
    return `S/ ${monto.toFixed(2)}`;
}

const columnas: ColumnaTabla<GastoFila>[] = [
    { clave: 'fecha', etiqueta: 'Fecha', render: (fila) => fila.fecha },
    { clave: 'descripcion', etiqueta: 'Descripción', render: (fila) => fila.descripcion },
    { clave: 'tipo', etiqueta: 'Comprobante', render: (fila) => etiquetasComprobante[fila.tipoComprobante] ?? fila.tipoComprobante },
    { clave: 'monto', etiqueta: 'Monto', render: (fila) => <span className="font-mono">{formatearMonto(fila.monto)}</span> },
    { clave: 'usuario', etiqueta: 'Registrado por', render: (fila) => fila.usuario },
];

export default function GastosIndex({ gastos, totalesPorMes, totalAnio, anio, puedeGestionar }: Props) {
    const anios = [anio - 1, anio, anio + 1];

    const cambiarAnio = (nuevoAnio: number) => {
        router.get(route('panel.gastos.index'), { anio: nuevoAnio }, { preserveState: true });
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Gastos" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">Gastos</h1>
                    {puedeGestionar && (
                        <Button asChild>
                            <Link href={route('panel.gastos.create')}>Registrar gasto</Link>
                        </Button>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {anios.map((opcion) => (
                        <Button key={opcion} type="button" size="sm" variant={opcion === anio ? 'default' : 'outline'} onClick={() => cambiarAnio(opcion)}>
                            {opcion}
                        </Button>
                    ))}
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-md border p-4">
                        <p className="text-muted-foreground text-xs">Total del año {anio}</p>
                        <p className="font-mono text-2xl">{formatearMonto(totalAnio)}</p>
                    </div>
                    {Object.entries(totalesPorMes).map(([mes, total]) => (
                        <div key={mes} className="rounded-md border p-4">
                            <p className="text-muted-foreground text-xs">{mes}</p>
                            <p className="font-mono text-lg">{formatearMonto(total)}</p>
                        </div>
                    ))}
                </div>

                <TablaResponsiva
                    columnas={columnas}
                    filas={gastos}
                    obtenerLlave={(fila) => fila.id}
                    vacio="No hay gastos registrados en este año."
                    acciones={(fila) => (
                        <>
                            {fila.tieneFoto && (
                                <Button variant="outline" size="sm" asChild>
                                    <a href={route('panel.gastos.comprobante', fila.id)} target="_blank" rel="noreferrer">
                                        Ver comprobante
                                    </a>
                                </Button>
                            )}
                            {puedeGestionar && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('panel.gastos.edit', fila.id)}>Editar</Link>
                                </Button>
                            )}
                        </>
                    )}
                />
            </div>
        </PanelLayout>
    );
}
