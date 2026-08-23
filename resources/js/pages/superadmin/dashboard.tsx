import { Button } from '@/components/ui/button';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import SuperadminLayout from '@/layouts/superadmin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface JassFila {
    id: number;
    nombre: string;
    socios: number;
    recaudacionAnio: number;
    porcentajeMorosidad: number;
}

interface Props {
    jass: JassFila[];
    totales: { jass: number; socios: number; recaudacionAnio: number };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Superadmin', href: '/superadmin' }];

function formatearMonto(monto: number): string {
    return `S/ ${monto.toFixed(2)}`;
}

const columnas: ColumnaTabla<JassFila>[] = [
    { clave: 'nombre', etiqueta: 'JASS', render: (fila) => fila.nombre },
    { clave: 'socios', etiqueta: 'Socios afiliados', render: (fila) => String(fila.socios) },
    { clave: 'recaudacion', etiqueta: 'Recaudación del año', render: (fila) => <span className="font-mono">{formatearMonto(fila.recaudacionAnio)}</span> },
    { clave: 'morosidad', etiqueta: '% morosidad', render: (fila) => `${fila.porcentajeMorosidad}%` },
];

export default function SuperadminDashboard({ jass, totales }: Props) {
    return (
        <SuperadminLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel superadmin" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-lg font-semibold">Estadísticas globales</h1>

                <div className="grid gap-3 sm:grid-cols-3">
                    <div className="rounded-md border p-4">
                        <p className="text-muted-foreground text-xs">JASS registradas</p>
                        <p className="font-mono text-2xl">{totales.jass}</p>
                    </div>
                    <div className="rounded-md border p-4">
                        <p className="text-muted-foreground text-xs">Socios afiliados (total)</p>
                        <p className="font-mono text-2xl">{totales.socios}</p>
                    </div>
                    <div className="rounded-md border p-4">
                        <p className="text-muted-foreground text-xs">Recaudación del año (total)</p>
                        <p className="font-mono text-2xl">{formatearMonto(totales.recaudacionAnio)}</p>
                    </div>
                </div>

                <TablaResponsiva
                    columnas={columnas}
                    filas={jass}
                    obtenerLlave={(fila) => fila.id}
                    vacio="Todavía no hay JASS registradas."
                    acciones={(fila) => (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('supervision.dashboard', fila.id)}>Supervisar</Link>
                        </Button>
                    )}
                />
            </div>
        </SuperadminLayout>
    );
}
