import { Button } from '@/components/ui/button';
import BadgeEstado from '@/components/yaku/badge-estado';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ReunionFila {
    id: number;
    titulo: string;
    fecha: string;
    hora: string;
    lugar: string;
    estado: 'programada' | 'cancelada' | 'finalizada';
}

interface Props {
    reuniones: ReunionFila[];
    puedeGestionar: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Reuniones', href: '/panel/reuniones' },
];

const variantePorEstado = {
    programada: 'exito',
    cancelada: 'error',
    finalizada: 'neutro',
} as const;

const etiquetaPorEstado: Record<string, string> = {
    programada: 'Programada',
    cancelada: 'Cancelada',
    finalizada: 'Finalizada',
};

const columnas: ColumnaTabla<ReunionFila>[] = [
    { clave: 'fecha', etiqueta: 'Fecha', render: (fila) => fila.fecha },
    { clave: 'hora', etiqueta: 'Hora', render: (fila) => fila.hora.slice(0, 5) },
    { clave: 'titulo', etiqueta: 'Título', render: (fila) => fila.titulo },
    { clave: 'lugar', etiqueta: 'Lugar', render: (fila) => fila.lugar },
    {
        clave: 'estado',
        etiqueta: 'Estado',
        render: (fila) => <BadgeEstado variante={variantePorEstado[fila.estado]}>{etiquetaPorEstado[fila.estado]}</BadgeEstado>,
    },
];

export default function ReunionesIndex({ reuniones, puedeGestionar }: Props) {
    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Reuniones" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">Reuniones</h1>
                    {puedeGestionar && (
                        <Button asChild>
                            <Link href={route('panel.reuniones.create')}>Nueva reunión</Link>
                        </Button>
                    )}
                </div>

                <TablaResponsiva
                    columnas={columnas}
                    filas={reuniones}
                    obtenerLlave={(fila) => fila.id}
                    vacio="No hay reuniones registradas."
                    acciones={(fila) => (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('panel.reuniones.show', fila.id)}>Ver</Link>
                        </Button>
                    )}
                />
            </div>
        </PanelLayout>
    );
}
