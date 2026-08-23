import { Button } from '@/components/ui/button';
import BadgeEstado from '@/components/yaku/badge-estado';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface AnuncioFila {
    id: number;
    titulo: string;
    contenido: string;
    fecha_publicacion: string;
    estado: 'visible' | 'inactivo';
}

interface Props {
    anuncios: AnuncioFila[];
    puedeGestionar: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Anuncios', href: '/panel/anuncios' },
];

const columnas: ColumnaTabla<AnuncioFila>[] = [
    { clave: 'fecha_publicacion', etiqueta: 'Fecha', render: (fila) => fila.fecha_publicacion },
    { clave: 'titulo', etiqueta: 'Título', render: (fila) => fila.titulo },
    {
        clave: 'estado',
        etiqueta: 'Estado',
        render: (fila) => <BadgeEstado variante={fila.estado === 'visible' ? 'exito' : 'neutro'}>{fila.estado === 'visible' ? 'Visible' : 'Inactivo'}</BadgeEstado>,
    },
];

export default function AnunciosIndex({ anuncios, puedeGestionar }: Props) {
    const eliminar = (fila: AnuncioFila) => {
        if (!confirm(`¿Eliminar el anuncio "${fila.titulo}"? Esta acción requiere confirmar tu contraseña.`)) {
            return;
        }

        router.delete(route('panel.anuncios.destroy', fila.id));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Anuncios" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">Anuncios</h1>
                    {puedeGestionar && (
                        <Button asChild>
                            <Link href={route('panel.anuncios.create')}>Nuevo anuncio</Link>
                        </Button>
                    )}
                </div>

                <TablaResponsiva
                    columnas={columnas}
                    filas={anuncios}
                    obtenerLlave={(fila) => fila.id}
                    vacio="No hay anuncios registrados."
                    acciones={
                        puedeGestionar
                            ? (fila) => (
                                  <>
                                      <Button variant="outline" size="sm" asChild>
                                          <Link href={route('panel.anuncios.edit', fila.id)}>Editar</Link>
                                      </Button>
                                      <Button variant="outline" size="sm" onClick={() => eliminar(fila)}>
                                          Eliminar
                                      </Button>
                                  </>
                              )
                            : undefined
                    }
                />
            </div>
        </PanelLayout>
    );
}
