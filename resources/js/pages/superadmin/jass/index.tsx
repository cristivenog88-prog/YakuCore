import { Button } from '@/components/ui/button';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import SuperadminLayout from '@/layouts/superadmin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface JassFila {
    id: number;
    uuid: string;
    nombre: string;
    comunidad: string;
    distrito: string;
    tiene_medidor: boolean;
    prefijo: string;
}

interface Props {
    jass: JassFila[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Superadmin', href: '/superadmin' },
    { title: 'JASS', href: '/superadmin/jass' },
];

const columnas: ColumnaTabla<JassFila>[] = [
    { clave: 'nombre', etiqueta: 'Nombre', render: (fila) => fila.nombre },
    { clave: 'comunidad', etiqueta: 'Comunidad', render: (fila) => fila.comunidad },
    { clave: 'distrito', etiqueta: 'Distrito', render: (fila) => fila.distrito },
    { clave: 'tiene_medidor', etiqueta: 'Medidor', render: (fila) => (fila.tiene_medidor ? 'Sí' : 'No') },
    { clave: 'prefijo', etiqueta: 'Prefijo', render: (fila) => fila.prefijo },
];

export default function JassIndex({ jass }: Props) {
    const eliminar = (fila: JassFila) => {
        if (!confirm(`¿Eliminar la JASS "${fila.nombre}"? Esta acción requiere confirmar tu contraseña.`)) {
            return;
        }

        router.delete(route('superadmin.jass.destroy', fila.uuid));
    };

    return (
        <SuperadminLayout breadcrumbs={breadcrumbs}>
            <Head title="JASS" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">JASS registradas</h1>
                    <Button asChild>
                        <Link href={route('superadmin.jass.create')}>Nueva JASS</Link>
                    </Button>
                </div>

                <TablaResponsiva
                    columnas={columnas}
                    filas={jass}
                    obtenerLlave={(fila) => fila.id}
                    vacio="Todavía no hay JASS registradas."
                    acciones={(fila) => (
                        <>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('supervision.dashboard', fila.uuid)}>Supervisar</Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('superadmin.jass.usuarios.index', fila.uuid)}>Usuarios</Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('superadmin.jass.edit', fila.uuid)}>Editar</Link>
                            </Button>
                            <Button variant="outline" size="sm" onClick={() => eliminar(fila)}>
                                Eliminar
                            </Button>
                        </>
                    )}
                />
            </div>
        </SuperadminLayout>
    );
}
