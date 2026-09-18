import { Button } from '@/components/ui/button';
import BadgeEstado from '@/components/yaku/badge-estado';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface AsistenciaFila {
    id: number;
    socio_id: number;
    estado: 'presente' | 'ausente';
    socio: { id: number; nombres: string; apellidos: string };
}

interface ReunionProps {
    id: number;
    uuid: string;
    titulo: string;
    descripcion: string | null;
    fecha: string;
    hora: string;
    lugar: string;
    puntosTratar: string | null;
    estado: 'programada' | 'cancelada' | 'finalizada';
}

interface Props {
    reunion: ReunionProps;
    puedeGestionar: boolean;
    puedeTomarAsistencia: boolean;
    asistencias: AsistenciaFila[];
}

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

const columnasAsistencia: ColumnaTabla<AsistenciaFila>[] = [
    { clave: 'socio', etiqueta: 'Socio', render: (fila) => `${fila.socio.apellidos}, ${fila.socio.nombres}` },
    {
        clave: 'estado',
        etiqueta: 'Estado',
        render: (fila) => (
            <BadgeEstado variante={fila.estado === 'presente' ? 'exito' : 'error'}>{fila.estado === 'presente' ? 'Presente' : 'Ausente'}</BadgeEstado>
        ),
    },
];

export default function ReunionShow({ reunion, puedeGestionar, puedeTomarAsistencia, asistencias }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Panel', href: '/panel' },
        { title: 'Reuniones', href: '/panel/reuniones' },
        { title: reunion.titulo, href: route('panel.reuniones.show', reunion.uuid) },
    ];

    const cancelar = () => {
        if (!confirm('¿Cancelar esta reunión? No podrá reprogramarse; deberás crear una nueva.')) {
            return;
        }

        router.post(route('panel.reuniones.cancelar', reunion.uuid));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={reunion.titulo} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-lg font-semibold wrap-break-word">{reunion.titulo}</h1>
                            <BadgeEstado variante={variantePorEstado[reunion.estado]}>{etiquetaPorEstado[reunion.estado]}</BadgeEstado>
                        </div>
                        <dl className="text-muted-foreground mt-1 flex flex-col gap-0.5 text-sm">
                            <div>
                                Fecha: {reunion.fecha} — {reunion.hora}
                            </div>
                            <div>Lugar: {reunion.lugar}</div>
                            {reunion.descripcion && <div className="whitespace-pre-line">Descripción: {reunion.descripcion}</div>}
                            {reunion.puntosTratar && <div className="whitespace-pre-line">Puntos a tratar: {reunion.puntosTratar}</div>}
                        </dl>
                    </div>

                    {puedeGestionar && (
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <Link href={route('panel.reuniones.edit', reunion.uuid)}>Editar</Link>
                            </Button>
                            <Button variant="outline" onClick={cancelar}>
                                Cancelar reunión
                            </Button>
                        </div>
                    )}
                </div>

                {puedeTomarAsistencia && (
                    <Button asChild className="w-fit">
                        <Link href={route('panel.reuniones.asistencia.create', reunion.uuid)}>Tomar asistencia</Link>
                    </Button>
                )}

                {reunion.estado === 'finalizada' && (
                    <div className="flex flex-col gap-3">
                        <h2 className="font-medium">Asistencia</h2>
                        <TablaResponsiva
                            columnas={columnasAsistencia}
                            filas={asistencias}
                            obtenerLlave={(fila) => fila.id}
                            vacio="Sin registro de asistencia."
                        />
                    </div>
                )}
            </div>
        </PanelLayout>
    );
}
