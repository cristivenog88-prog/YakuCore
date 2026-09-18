import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface SocioFila {
    id: number;
    uuid: string;
    nombres: string;
    apellidos: string;
    dni: string;
    telefono: string | null;
}

interface Props {
    socios: SocioFila[];
    busqueda: string;
    puedeGestionar: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Socios', href: '/panel/socios' },
];

const columnas: ColumnaTabla<SocioFila>[] = [
    { clave: 'apellidos', etiqueta: 'Apellidos', render: (fila) => fila.apellidos },
    { clave: 'nombres', etiqueta: 'Nombres', render: (fila) => fila.nombres },
    { clave: 'dni', etiqueta: 'DNI', render: (fila) => <span className="font-mono">{fila.dni}</span> },
    { clave: 'telefono', etiqueta: 'Teléfono', render: (fila) => fila.telefono ?? '—' },
];

export default function SociosIndex({ socios, busqueda, puedeGestionar }: Props) {
    const [valorBusqueda, setValorBusqueda] = useState(busqueda);

    const buscar: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('panel.socios.index'), { buscar: valorBusqueda }, { preserveState: true });
    };

    const eliminar = (fila: SocioFila) => {
        if (!confirm(`¿Eliminar a "${fila.nombres} ${fila.apellidos}"? Esta acción requiere confirmar tu contraseña.`)) {
            return;
        }

        router.delete(route('panel.socios.destroy', fila.uuid));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Socios" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">Socios</h1>
                    {puedeGestionar && (
                        <Button asChild>
                            <Link href={route('panel.socios.create')}>Nuevo socio</Link>
                        </Button>
                    )}
                </div>

                <form onSubmit={buscar} className="flex max-w-sm gap-2">
                    <Input
                        value={valorBusqueda}
                        onChange={(e) => setValorBusqueda(e.target.value)}
                        placeholder="Buscar por DNI o apellidos"
                    />
                    <Button type="submit" variant="outline">
                        Buscar
                    </Button>
                </form>

                <TablaResponsiva
                    columnas={columnas}
                    filas={socios}
                    obtenerLlave={(fila) => fila.id}
                    vacio="No se encontraron socios."
                    acciones={(fila) => (
                        <>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('panel.socios.show', fila.uuid)}>Ver ficha</Link>
                            </Button>
                            {puedeGestionar && (
                                <Button variant="outline" size="sm" onClick={() => eliminar(fila)}>
                                    Eliminar
                                </Button>
                            )}
                        </>
                    )}
                />
            </div>
        </PanelLayout>
    );
}
